<?php

return [
    'local_domain' => env(
        'CMS_LOCAL_DOMAIN',
        strtolower(basename(str_replace('\\', '/', base_path()))).'.test',
    ),

    'development_host_suffixes' => [
        '.test',
        '.localhost',
    ],

    'default_template_settings' => [
        'primary_color' => '#ffa300',
        'secondary_color' => '#272720',
        'tertiary_color' => '#00a287',
        'accent_color' => '#ffa300',
        'surface_color' => '#ffffff',
        'canvas_color' => '#ffffff',
        'light_color' => '#f0f0f0',
        'grey_color' => '#e0e0e0',
        'dark_color' => '#2d2d29',
        'ink_color' => '#2d2d29',
        'muted_ink_color' => '#6f6f68',
        'base_font_family' => '"Open Sans", Arial, sans-serif',
        'heading_font_family' => '"Open Sans", Arial, sans-serif',
        'base_font_google_url' => '',
        'heading_font_google_url' => '',
        'title_style' => 'strong',
        'button_style' => 'solid',
        'button_radius' => '3px',
        'content_width' => '1400px',
        'wrapper_width' => '1400px',
        'logo_width' => '150px',
        'logo_height' => '75px',
        'hero_height' => '448px',
        'logo_path' => 'site/templates/default/assets/logo.svg',
        'hero_image_path' => 'site/templates/default/assets/default-eyecatcher-image.jpg',
        'show_usp_bar' => true,
        'sticky_header' => true,
        'show_hero' => true,
        'show_footer_credit' => true,
        'search_enabled' => false,
        'usp_items' => [
            'Responsive maatwerk',
            'SEO vriendelijke basis',
            'Veilig en snel beheer',
        ],
        'footer_contact_title' => 'Contactgegevens',
        'footer_contact_text' => '',
        'footer_social_title' => 'Social media',
        'footer_social_text' => 'Volg ons op social media en blijf op de hoogte.',
        'footer_content_title' => 'Over ons',
        'footer_content_text' => 'Een modern Laravel basisplatform voor maatwerk websites.',
        'footer_credit_label' => 'HPU internet services',
        'footer_credit_url' => 'https://hpu.nl/',
        'social_placement' => 'footer',
        'contact_form_placement' => 'footer',
    ],

    'button_styles' => [
        'solid' => 'Solid',
        'outline' => 'Outline',
        'soft' => 'Soft',
    ],

    'placement_options' => [
        'none' => 'Not shown',
        'header' => 'Header',
        'footer' => 'Footer',
    ],

    'social_platforms' => [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'x' => 'X',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
    ],

    'public_integrations' => [
        'google_analytics_measurement_id' => 'Google Analytics measurement ID',
        'google_tag_manager_container_id' => 'Google Tag Manager container ID',
        'matomo_url' => 'Matomo URL',
        'matomo_site_id' => 'Matomo site ID',
        'meta_pixel_id' => 'Meta Pixel ID',
    ],
];
