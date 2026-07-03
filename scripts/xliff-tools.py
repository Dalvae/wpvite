#!/usr/bin/env python3
"""XLIFF 1.2 tools for WPML translation pipeline.

Functions:
  extract_sources(path) — extract all translatable strings from XLIFF
  extract_untranslated(path) — get only untranslated strings
  apply_translations(input_path, output_path, translations) — write translated XLIFF

CLI:
  python scripts/xliff-tools.py extract <file.xliff>
  python scripts/xliff-tools.py extract-untranslated <file.xliff>
  python scripts/xliff-tools.py apply <in.xliff> <out.xliff> <translations.json>
"""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path
from typing import Any


def extract_sources(path: str | Path) -> list[dict[str, str]]:
    """Extract all translatable source strings from an XLIFF 1.2 file.

    Returns list of {id, resname, source} dicts.
    """
    content = Path(path).read_text(encoding="utf-8")
    results: list[dict[str, str]] = []

    pattern = re.compile(
        r'<trans-unit\s+([^>]*?)>'
        r'.*?<source>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*</source>',
        re.DOTALL,
    )

    for match in pattern.finditer(content):
        attrs = match.group(1)
        source = match.group(2).strip()

        id_match = re.search(r'id="([^"]*)"', attrs)
        resname_match = re.search(r'resname="([^"]*)"', attrs)
        unit_id = id_match.group(1) if id_match else ""
        resname = resname_match.group(1) if resname_match else ""

        if source:
            results.append({"id": unit_id, "resname": resname, "source": source})

    return results


def extract_untranslated(path: str | Path) -> list[dict[str, str]]:
    """Extract only untranslated source strings (target empty or same as source)."""
    content = Path(path).read_text(encoding="utf-8")
    results: list[dict[str, str]] = []

    pattern = re.compile(
        r'<trans-unit\s+([^>]*?)>(.*?)</trans-unit>',
        re.DOTALL,
    )

    for match in pattern.finditer(content):
        attrs = match.group(1)
        body = match.group(2)

        src_m = re.search(r'<source>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*</source>', body, re.DOTALL)
        tgt_m = re.search(r'<target[^>]*>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*</target>', body, re.DOTALL)

        if not src_m:
            continue

        source = src_m.group(1).strip()
        target = tgt_m.group(1).strip() if tgt_m else ""

        if not source:
            continue

        if target and target != source:
            continue

        id_match = re.search(r'id="([^"]*)"', attrs)
        resname_match = re.search(r'resname="([^"]*)"', attrs)
        unit_id = id_match.group(1) if id_match else ""
        resname = resname_match.group(1) if resname_match else ""

        results.append({"id": unit_id, "resname": resname, "source": source})

    return results


def apply_translations(
    input_path: str | Path,
    output_path: str | Path,
    translations: dict[str, str],
) -> int:
    """Apply translations to an XLIFF file and save.

    Args:
        input_path: source XLIFF file
        output_path: where to write translated XLIFF
        translations: dict mapping trans-unit id -> translated text

    Returns number of translations applied.
    """
    content = Path(input_path).read_text(encoding="utf-8")
    applied = 0

    def replace_target(match: re.Match) -> str:
        nonlocal applied
        full = match.group(0)
        attrs = match.group(1)
        id_m = re.search(r'id="([^"]*)"', attrs)
        unit_id = id_m.group(1) if id_m else ""

        if unit_id not in translations:
            return full

        translated = translations[unit_id]
        applied += 1

        new_target = f'<target state="translated"><![CDATA[{translated}]]></target>'

        if "<target" in full:
            full = re.sub(
                r"<target[^>]*>.*?</target>",
                new_target,
                full,
                flags=re.DOTALL,
            )
        else:
            full = re.sub(
                r"(</source>)",
                r"\1" + new_target,
                full,
            )

        return full

    pattern = re.compile(
        r'<trans-unit\s+([^>]*?)>.*?</trans-unit>',
        re.DOTALL,
    )
    result = pattern.sub(replace_target, content)

    Path(output_path).parent.mkdir(parents=True, exist_ok=True)
    Path(output_path).write_text(result, encoding="utf-8")

    return applied


def main() -> None:
    if len(sys.argv) < 3:
        print("Usage:")
        print("  python xliff-tools.py extract <file.xliff>")
        print("  python xliff-tools.py extract-untranslated <file.xliff>")
        print("  python xliff-tools.py apply <in.xliff> <out.xliff> <translations.json>")
        sys.exit(1)

    cmd = sys.argv[1]

    if cmd == "extract":
        path = sys.argv[2]
        sources = extract_sources(path)
        print(json.dumps(sources, ensure_ascii=False, indent=2))

    elif cmd == "extract-untranslated":
        path = sys.argv[2]
        sources = extract_untranslated(path)
        print(json.dumps(sources, ensure_ascii=False, indent=2))

    elif cmd == "apply":
        if len(sys.argv) < 5:
            print("Usage: python xliff-tools.py apply <in.xliff> <out.xliff> <translations.json>")
            sys.exit(1)
        in_path = sys.argv[2]
        out_path = sys.argv[3]
        trans_path = sys.argv[4]
        translations = json.loads(Path(trans_path).read_text(encoding="utf-8"))
        n = apply_translations(in_path, out_path, translations)
        print(f"applied {n} translations -> {out_path}")

    else:
        print(f"Unknown command: {cmd}")
        sys.exit(1)


if __name__ == "__main__":
    main()
