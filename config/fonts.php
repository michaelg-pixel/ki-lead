<?php
/**
 * Font Configuration für Freebie Editor
 * Definiert verfügbare Schriftarten und Größen
 */

return [
    'fonts' => [
        // Modern & Clean
        'Poppins' => 'Modern & Clean',
        'Inter' => 'Modern & Clean',
        'Roboto' => 'Modern & Clean',
        'Open Sans' => 'Modern & Clean',
        'Montserrat' => 'Modern & Clean',
        'Lato' => 'Modern & Clean',
        
        // Bold & Impact
        'Anton' => 'Bold & Impact',
        'Bebas Neue' => 'Bold & Impact',
        'Oswald' => 'Bold & Impact',
        'Barlow Condensed' => 'Bold & Impact',
        
        // Elegant & Light
        'Raleway' => 'Elegant & Light',
        'Playfair Display' => 'Elegant & Light',
        'Lora' => 'Elegant & Light',
        'Cormorant' => 'Elegant & Light',
        
        // Classic & Serif
        'Merriweather' => 'Classic & Serif',
        'PT Serif' => 'Classic & Serif',
        'Crimson Text' => 'Classic & Serif',
        
        // System Fonts
        'Verdana' => 'System Fonts',
        'Arial' => 'System Fonts',
        'Georgia' => 'System Fonts',
        'Times New Roman' => 'System Fonts',
    ],
    
    'sizes' => [
        'preheadline' => [10, 12, 14, 16, 18, 20, 22],
        'headline' => [24, 28, 32, 36, 40, 48, 56, 64, 72, 80],
        'subheadline' => [14, 16, 18, 20, 22, 24, 28, 32],
        'bulletpoints' => [12, 14, 16, 18, 20, 22, 24],
    ],
    
    'defaults' => [
        'preheadline_font' => 'Poppins',
        'preheadline_size' => 14,
        'headline_font' => 'Poppins',
        'headline_size' => 48,
        'subheadline_font' => 'Poppins',
        'subheadline_size' => 20,
        'bulletpoints_font' => 'Poppins',
        'bulletpoints_size' => 16,
    ],
    
    // Google Fonts URLs für die verschiedenen Schriften
    'google_fonts_url' => 'https://fonts.googleapis.com/css2?family=' . implode('&family=', [
        'Poppins:wght@300;400;500;600;700;800',
        'Inter:wght@300;400;500;600;700;800',
        'Roboto:wght@300;400;500;700;900',
        'Open+Sans:wght@300;400;600;700;800',
        'Montserrat:wght@300;400;500;600;700;800;900',
        'Lato:wght@300;400;700;900',
        'Anton',
        'Bebas+Neue',
        'Oswald:wght@300;400;500;600;700',
        'Barlow+Condensed:wght@300;400;600;700;800;900',
        'Raleway:wght@300;400;500;600;700;800',
        'Playfair+Display:wght@400;500;600;700;800;900',
        'Lora:wght@400;500;600;700',
        'Cormorant:wght@300;400;500;600;700',
        'Merriweather:wght@300;400;700;900',
        'PT+Serif:wght@400;700',
        'Crimson+Text:wght@400;600;700',
    ]) . '&display=swap'
];
