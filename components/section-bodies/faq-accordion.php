<?php
$args = wp_parse_args(
    $args,
    array(
        'kicker' => '',
        'title' => '',
        'intro' => '',
        'items' => array(),
        'header_preset' => 'page-section',
    )
);

$items = array_values(array_filter((array) $args['items'], 'is_array'));
$has_header = trim((string) $args['kicker']) !== '' || trim((string) $args['title']) !== '' || trim((string) $args['intro']) !== '';
?>
<div class="section-faq content-stack content-stack--section">
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
        <div class="section-faq__list">
            <?php foreach ($items as $item) : ?>
                <?php
                $question = trim((string) ($item['question'] ?? $item['title'] ?? ''));
                $answer = trim((string) ($item['answer'] ?? $item['content'] ?? ''));
                $answer_html = trim((string) ($item['answer_html'] ?? ''));
                if ($question === '' || ($answer === '' && $answer_html === '')) {
                    continue;
                }
                ?>
                <details class="collapse collapse-plus section-faq__item border border-base-300 bg-base-100 shadow-md" data-reveal="true">
                    <summary class="collapse-title section-faq__question">
                        <?php echo esc_html($question); ?>
                    </summary>
                    <div class="collapse-content section-faq__answer">
                        <?php if ($answer !== '') : ?>
                            <p><?php echo esc_html($answer); ?></p>
                        <?php endif; ?>
                        <?php if ($answer_html !== '') : ?>
                            <div class="page-prose">
                                <?php echo wp_kses_post($answer_html); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
