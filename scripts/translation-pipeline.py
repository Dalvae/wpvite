#!/usr/bin/env python3
"""XLIFF translation pipeline — init, qa, pack stages.

The actual translation is done by Kimi 2.5 (or any AI) editing the XLIFF files
in tmp/translation-pipeline/translated/ using the translator prompt.

Subcommands:
- init:  unpack an exported XLIFF zip into a working folder
- qa:    compare source vs translated files and validate structure
- pack:  create import-ready zip from translated files

Usage:
  # 1. Export XLIFF from WPML
  pnpm wpml:export --extract

  # 2. Initialize pipeline workdir from the exported ZIP
  python scripts/translation-pipeline.py init translations/xliff-source/<file>.zip

  # 3. Give Kimi the files in tmp/translation-pipeline/translated/ + the prompt
  #    Kimi edits the <target> values in each XLIFF file

  # 4. QA the translations
  python scripts/translation-pipeline.py qa

  # 5. Package for import
  python scripts/translation-pipeline.py pack translations/translated-import.zip

  # 6. Import back into WPML
  pnpm wpml:import --zip translations/translated-import.zip
"""

from __future__ import annotations

import argparse
import json
import re
import shutil
import sys
import zipfile
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Any

XLIFF_NS = {"x": "urn:oasis:names:tc:xliff:document:1.2"}
SERIALIZED_PREFIX_RE = re.compile(
    r'^([aA]):(\d+):\{([iI]):(\d+);([sS]):(\d+):"',
    re.DOTALL,
)
SERIALIZED_LOOSE_RE = re.compile(
    r'^([aA]):\d+:\{([iI]):\d+;([sS]):\d+:"([\s\S]*)";\s*\}$',
    re.DOTALL,
)


def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def repo_root() -> Path:
    return Path(__file__).resolve().parents[1]


def default_workdir() -> Path:
    return repo_root() / "tmp" / "translation-pipeline"


def list_xliff_files(folder: Path) -> list[Path]:
    return sorted(folder.glob("*.xliff"))


def non_translatable_value(value: str) -> bool:
    stripped = value.strip()
    if stripped == "":
        return True
    if stripped.startswith(("http://", "https://", "tel:", "mailto:", "xr:d:")):
        return True
    return False


def _char_index_for_utf8_bytes(text: str, start_char: int, byte_count: int) -> int | None:
    consumed = 0
    index = start_char
    while index < len(text) and consumed < byte_count:
        consumed += len(text[index].encode("utf-8"))
        index += 1
    if consumed != byte_count:
        return None
    return index


def parse_serialized_single_string_strict(value: str) -> dict[str, Any] | None:
    match = SERIALIZED_PREFIX_RE.match(value)
    if not match:
        return None
    array_count = int(match.group(2))
    item_index = int(match.group(4))
    declared_length = int(match.group(6))
    start = match.end()
    end = _char_index_for_utf8_bytes(value, start, declared_length)
    if end is None:
        return None
    suffix = value[end:].strip()
    if suffix != '";}':
        return None
    inner = value[start:end]
    return {"array_count": array_count, "item_index": item_index, "inner": inner}


def parse_serialized_single_string_loose(value: str) -> str | None:
    match = SERIALIZED_LOOSE_RE.match(value.strip())
    if not match:
        return None
    return match.group(4)


def build_serialized_single_string(inner: str, array_count: int = 1, item_index: int = 0) -> str:
    byte_len = len(inner.encode("utf-8"))
    return f'a:{array_count}:{{i:{item_index};s:{byte_len}:"{inner}";}}'


def normalize_serialized_target(source_value: str, target_value: str) -> tuple[str, bool]:
    source_meta = parse_serialized_single_string_strict(source_value)
    if source_meta is None:
        return target_value, False
    target_meta = parse_serialized_single_string_strict(target_value)
    if target_meta is not None:
        inner = target_meta["inner"]
    else:
        loose_inner = parse_serialized_single_string_loose(target_value)
        inner = loose_inner if loose_inner is not None else target_value
    normalized = build_serialized_single_string(
        inner=inner,
        array_count=source_meta["array_count"],
        item_index=source_meta["item_index"],
    )
    return normalized, normalized != target_value


def parse_units(path: Path) -> list[dict[str, str]]:
    tree = ET.parse(path)
    root = tree.getroot()
    units: list[dict[str, str]] = []
    for trans_unit in root.findall(".//x:trans-unit", XLIFF_NS):
        unit_id = trans_unit.attrib.get("id", "")
        source_node = trans_unit.find("x:source", XLIFF_NS)
        target_node = trans_unit.find("x:target", XLIFF_NS)
        source = source_node.text if source_node is not None and source_node.text is not None else ""
        target = target_node.text if target_node is not None and target_node.text is not None else ""
        units.append({"id": unit_id, "source": source, "target": target})
    return units


# ── INIT ──────────────────────────────────────────────────────────────────

def cmd_init(args: argparse.Namespace) -> None:
    input_zip = Path(args.input_zip).resolve()
    if not input_zip.is_file():
        fail(f"Input zip not found: {input_zip}")

    workdir = Path(args.workdir).resolve()
    if workdir.exists():
        if not args.force:
            fail(f"Workdir exists: {workdir} (use --force to replace)")
        shutil.rmtree(workdir)

    input_dir = workdir / "input"
    translated_dir = workdir / "translated"
    extract_dir = workdir / "_extract"
    input_dir.mkdir(parents=True, exist_ok=True)
    translated_dir.mkdir(parents=True, exist_ok=True)
    extract_dir.mkdir(parents=True, exist_ok=True)

    with zipfile.ZipFile(input_zip, "r") as archive:
        archive.extractall(extract_dir)

    extracted_files = sorted(extract_dir.rglob("*.xliff"))
    if not extracted_files:
        fail(f"No .xliff files found in zip: {input_zip}")

    for source_file in extracted_files:
        destination = input_dir / source_file.name
        shutil.copy2(source_file, destination)
        shutil.copy2(source_file, translated_dir / source_file.name)

    shutil.rmtree(extract_dir)

    manifest = {
        "input_zip": str(input_zip),
        "file_count": len(extracted_files),
        "files": [file.name for file in list_xliff_files(input_dir)],
    }
    (workdir / "manifest.json").write_text(
        json.dumps(manifest, indent=2, ensure_ascii=True), encoding="utf-8"
    )

    print(f"Initialized workdir: {workdir}")
    print(f"Files: {manifest['file_count']}")
    print(f"\nNext step: translate the files in {translated_dir}/")
    print(f"Use the prompt at: prompts/translation/translator.prompt.md")


# ── QA ────────────────────────────────────────────────────────────────────

def compare_file_pair(source_file: Path, translated_file: Path) -> dict[str, Any]:
    report: dict[str, Any] = {
        "file": source_file.name,
        "errors": [],
        "warnings": [],
        "total_units": 0,
        "translated_units": 0,
        "untranslated_units": 0,
    }

    try:
        source_units = parse_units(source_file)
        translated_units = parse_units(translated_file)
    except ET.ParseError as exc:
        report["errors"].append(f"XML parse error: {exc}")
        return report

    report["total_units"] = len(source_units)
    src_by_id = {unit["id"]: unit for unit in source_units}
    trg_by_id = {unit["id"]: unit for unit in translated_units}

    missing_ids = sorted(set(src_by_id) - set(trg_by_id))
    extra_ids = sorted(set(trg_by_id) - set(src_by_id))
    if missing_ids:
        report["errors"].append(f"Missing trans-unit IDs: {missing_ids}")
    if extra_ids:
        report["errors"].append(f"Unexpected trans-unit IDs: {extra_ids}")

    for unit_id, source_unit in src_by_id.items():
        translated_unit = trg_by_id.get(unit_id)
        if translated_unit is None:
            continue

        if translated_unit["source"] != source_unit["source"]:
            report["errors"].append(f"source changed for id={unit_id}")

        source_value = source_unit["source"]
        target_value = translated_unit["target"]
        source_serialized = parse_serialized_single_string_strict(source_value)
        target_serialized = parse_serialized_single_string_strict(target_value)

        if source_serialized is not None:
            if target_serialized is None:
                report["errors"].append(f"serialized wrapper broken id={unit_id}")
            else:
                if target_serialized["array_count"] != source_serialized["array_count"]:
                    report["errors"].append(f"serialized array count changed id={unit_id}")
                if target_serialized["item_index"] != source_serialized["item_index"]:
                    report["errors"].append(f"serialized item index changed id={unit_id}")

        if target_value == "":
            report["warnings"].append(f"empty target for id={unit_id}")

        source_for_translation = source_serialized["inner"] if source_serialized is not None else source_value
        target_for_translation = target_serialized["inner"] if target_serialized is not None else target_value

        if target_for_translation == source_for_translation:
            if not non_translatable_value(source_for_translation):
                report["untranslated_units"] += 1
                report["warnings"].append(f"untranslated id={unit_id}")
        else:
            report["translated_units"] += 1

    return report


def repair_serialized_targets_in_file(path: Path) -> int:
    try:
        tree = ET.parse(path)
    except ET.ParseError:
        return 0

    root = tree.getroot()
    updates = 0
    for trans_unit in root.findall(".//x:trans-unit", XLIFF_NS):
        source_node = trans_unit.find("x:source", XLIFF_NS)
        target_node = trans_unit.find("x:target", XLIFF_NS)

        source_value = source_node.text if source_node is not None and source_node.text is not None else ""
        target_value = target_node.text if target_node is not None and target_node.text is not None else ""

        normalized_target, changed = normalize_serialized_target(source_value, target_value)
        if changed:
            if target_node is None:
                target_node = ET.SubElement(trans_unit, "{urn:oasis:names:tc:xliff:document:1.2}target")
            target_node.text = normalized_target
            updates += 1

    if updates > 0:
        tree.write(path, encoding="utf-8", xml_declaration=True)
    return updates


def repair_serialized_targets_in_folder(folder: Path) -> tuple[int, int]:
    files = list_xliff_files(folder)
    files_changed = 0
    units_changed = 0
    for file_path in files:
        changed = repair_serialized_targets_in_file(file_path)
        if changed > 0:
            files_changed += 1
            units_changed += changed
    return files_changed, units_changed


def cmd_qa(args: argparse.Namespace) -> None:
    workdir = Path(args.workdir).resolve()
    input_dir = workdir / "input"
    translated_dir = workdir / "translated"
    if not input_dir.is_dir() or not translated_dir.is_dir():
        fail(f"Missing input/translated directories under {workdir}")

    if bool(getattr(args, "repair_serialized", True)):
        files_fixed, units_fixed = repair_serialized_targets_in_folder(translated_dir)
        if units_fixed > 0:
            print(f"Serialized wrapper repair: {units_fixed} unit(s) across {files_fixed} file(s)")

    source_files = list_xliff_files(input_dir)
    translated_files = list_xliff_files(translated_dir)
    if not source_files:
        fail(f"No source xliff files in {input_dir}")

    source_map = {path.name: path for path in source_files}
    translated_map = {path.name: path for path in translated_files}

    global_errors: list[str] = []
    missing_files = sorted(set(source_map) - set(translated_map))
    extra_files = sorted(set(translated_map) - set(source_map))
    if missing_files:
        global_errors.append(f"Missing translated files: {missing_files}")
    if extra_files:
        global_errors.append(f"Unexpected translated files: {extra_files}")

    file_reports: list[dict[str, Any]] = []
    for file_name in sorted(set(source_map) & set(translated_map)):
        file_reports.append(compare_file_pair(source_map[file_name], translated_map[file_name]))

    summary = {
        "errors": global_errors,
        "files": file_reports,
        "totals": {
            "files": len(file_reports),
            "units": sum(item["total_units"] for item in file_reports),
            "translated": sum(item["translated_units"] for item in file_reports),
            "untranslated": sum(item["untranslated_units"] for item in file_reports),
            "file_errors": sum(1 for item in file_reports if item["errors"]),
        },
    }

    report_path = workdir / "qa-report.json"
    report_path.write_text(json.dumps(summary, indent=2, ensure_ascii=True), encoding="utf-8")

    print(f"QA report: {report_path}")
    print(
        "Totals: "
        f"{summary['totals']['translated']}/{summary['totals']['units']} translated, "
        f"{summary['totals']['untranslated']} untranslated"
    )
    if global_errors:
        print(f"Global errors: {len(global_errors)}")
    if summary["totals"]["file_errors"] > 0:
        print(f"Files with errors: {summary['totals']['file_errors']}")

    has_errors = bool(global_errors) or summary["totals"]["file_errors"] > 0
    if args.strict and has_errors:
        raise SystemExit(2)


# ── PACK ──────────────────────────────────────────────────────────────────

def cmd_pack(args: argparse.Namespace) -> None:
    workdir = Path(args.workdir).resolve()
    translated_dir = workdir / "translated"
    if not translated_dir.is_dir():
        fail(f"Missing translated folder: {translated_dir}")

    if bool(getattr(args, "repair_serialized", True)):
        files_fixed, units_fixed = repair_serialized_targets_in_folder(translated_dir)
        if units_fixed > 0:
            print(f"Serialized wrapper repair: {units_fixed} unit(s) across {files_fixed} file(s)")

    files = list_xliff_files(translated_dir)
    if not files:
        fail(f"No xliff files to package in {translated_dir}")

    output_zip = Path(args.output_zip).resolve()
    output_zip.parent.mkdir(parents=True, exist_ok=True)

    with zipfile.ZipFile(output_zip, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for file_path in files:
            archive.write(file_path, arcname=file_path.name)

    print(f"Created: {output_zip}")
    print(f"Files packaged: {len(files)}")


# ── CLI ───────────────────────────────────────────────────────────────────

def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="WPML XLIFF translation pipeline")
    subparsers = parser.add_subparsers(dest="command", required=True)

    # init
    init_p = subparsers.add_parser("init", help="Initialize workdir from XLIFF zip")
    init_p.add_argument("input_zip", help="Path to source zip exported from WPML")
    init_p.add_argument("--workdir", default=str(default_workdir()))
    init_p.add_argument("--force", action="store_true", help="Replace existing workdir")
    init_p.set_defaults(func=cmd_init)

    # qa
    qa_p = subparsers.add_parser("qa", help="Validate translated XLIFF files")
    qa_p.add_argument("--workdir", default=str(default_workdir()))
    qa_p.add_argument("--strict", action="store_true", help="Exit non-zero on errors")
    qa_p.add_argument("--no-repair-serialized", dest="repair_serialized", action="store_false")
    qa_p.set_defaults(repair_serialized=True, func=cmd_qa)

    # pack
    pack_p = subparsers.add_parser("pack", help="Create import zip from translated files")
    pack_p.add_argument("output_zip", help="Output zip path")
    pack_p.add_argument("--workdir", default=str(default_workdir()))
    pack_p.add_argument("--no-repair-serialized", dest="repair_serialized", action="store_false")
    pack_p.set_defaults(repair_serialized=True, func=cmd_pack)

    return parser


def main() -> None:
    parser = build_parser()
    args = parser.parse_args()
    args.func(args)


if __name__ == "__main__":
    main()
