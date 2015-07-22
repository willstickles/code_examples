<?php echo $save_success; ?>
<form method="post" action="">
    <div>

        <?php
        screen_icon('themes');
        $title_li = 'Site Map';
        $link_before = '';
        $link_after = '';

        echo wp_list_pages('title_li=' . $title_li .'&link_before=' . $link_before . '&link_after=' . $link_after .'&echo=0');

        ?>
    </div>
</form>