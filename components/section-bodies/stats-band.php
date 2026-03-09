<?php
$args = wp_parse_args(
    $args,
    array(
        'kicker' => '',
        'title' => '',
        'intro' => '',
        'items' => array(),
        'header_preset' => 'centered-display',
    )
);

$items = array_values(array_filter((array) $args['items'], 'is_array'));
$has_header = trim((string) $args['kicker']) !== '' || trim((string) $args['title']) !== '' || trim((string) $args['intro']) !== '';
?>
<div class="section-stats content-stack content-stack--section">
    <?php if ($has_header) : ?>
        <?php
        get_template_part(
            'components/section-header',
            null,
            array(
                'preset' => (string) $args['header_preset'],
                'kicker' => (string) $args['kicker'],
                'title' => (string) $args['title'],
                'intro' => (string) $args['intro'],
                'attributes' => array(
                    'data-reveal' => 'true',
                ),
            )
        );
        ?>
    <?php endif; ?>

    <?php if (!empty($items)) : ?>
        <div class="section-stats__grid">
            <?php foreach ($items as $item) : ?>
                <?php
                $value = trim((string) ($item['value'] ?? $item['kpi_value'] ?? ''));
                $label = trim((string) ($item['label'] ?? ''));
                if ($value === '') {
                    continue;
                }

                get_template_part(
                    'components/stat-tile',
                    null,
                    array(
                        'class' => 'section-stats__tile',
                        'value_parts' => starter_split_stat_value($value),
                        'value_tag' => 'p',
                        'value_class' => 'section-stats__value',
                        'value_aria_label' => $value,
                        'number_part_class' => 'section-stats__value-part section-stats__value-part--number',
                        'text_part_class' => 'section-stats__value-part section-stats__value-part--text',
                        'label' => $label,
                        'label_tag' => 'p',
                        'label_class' => 'section-stats__label',
                        'attributes' => array(
                            'data-reveal' => 'true',
                        ),
                    )
                );
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
