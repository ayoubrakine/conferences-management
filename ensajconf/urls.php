<?php
global $wpdb;
$results = $wpdb->get_results("SELECT id, title, url FROM conferences ORDER BY title DESC");

if ($results) {
    echo '<div style="background-color: #f9f9f9; width:800px;margin: 0 auto; padding: 20px;
     box-shadow: 0 8px 8px #7E7B7B; border-radius: 5px; border-color:black;">';
    echo '<h3 style="text-align: center; color: #333; font-size: 24px; margin-top: 0;">
    All Conferences</h3>';
    echo '<ul style="list-style: none; margin: 0; padding: 0;">';
    foreach ($results as $result) {
        $title = $result->title;
        $url = $result->url;
        echo '<li style="margin-bottom: 10px;   border-bottom: 1px solid #ccc; margin-left:170px; 
        margin-right:170px; padding: 10px 0;">';
        echo '<a style="color: black; text-decoration: none; font-size: 18px; 
        display: inline-block; margin-left: 10px; padding-left: 10px; position: relative;
        " href="' . $url . '" target="_blank"><span>' . $title . '</span></a>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
?>

<style>
a:hover span {
    
color:#0073aa;
    opacity: 0.2;
    transition: opacity 0.3s ease-in-out;
}
</style>