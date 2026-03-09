
</main>



</div>


<footer id="colophon" class="site-footer" role="contentinfo">
    <div class="brand-shell">
        <div class="footer-shell">
            <?php get_template_part('components/brand-lockup', null, array('show_tagline' => true)); ?>

            <div class="footer-meta">
                <p>&copy; <?php echo esc_html(date_i18n('Y')); ?> - <?php echo esc_html(get_bloginfo('name')); ?></p>
                <p><?php echo esc_html(get_bloginfo('description')); ?></p>
            </div>
        </div>
    </div>
</footer>

</div>

<?php wp_footer(); ?>

</body>
</html>
