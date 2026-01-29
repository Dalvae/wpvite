<?php

//    get_template_part(slug: 'components/button', args: [
//        'text' => 'My Button',
//        'href' => 'https://www.google.co.uk',
//        'target' => '_blank',
//        'class' => ''
//    ]);


$classes = 'text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2';

$args['class'] = implode(' ', array_merge(explode(' ', $classes), explode(' ', $args['class'])));

?>
<a href="<?php echo esc_url($args['href']); ?>" class="<?php echo esc_attr($args['class']); ?>" target="<?php echo esc_attr($args['target']); ?>">
    <?php echo $args['text']; ?>
</a>
