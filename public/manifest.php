<?php
/**
 * Manifest PWA Dinámico
 * Auto-detecta si estás en local o producción
 */

// Detectar entorno
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) ||
           str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');

// Base path según entorno
$basePath = $isLocal ? '/travel-agency-mvp/public' : '';

// Manifest data
$manifest = [
    "name" => "Travel Mayan World",
    "short_name" => "TWM",
    "description" => "Descubre la magia del mundo maya con tours personalizados por Guatemala, Belice y México. Reserva online, offline-first PWA con notificaciones push.",
    "version" => "2.0.0",
    "theme_color" => "#2E8B57",
    "background_color" => "#F5F5DC",
    "display" => "standalone",
    "display_override" => ["window-controls-overlay", "standalone", "minimal-ui"],
    "orientation" => "any",
    "scope" => $basePath . "/",
    "start_url" => $basePath . "/?utm_source=pwa&utm_medium=homescreen",
    "id" => "travel-mayan-world-pwa",
    "lang" => "es-GT",
    "dir" => "ltr",
    
    "categories" => ["travel", "tourism", "business", "lifestyle"],
    
    "icons" => [
        [
            "src" => $basePath . "/assets/images/icons/icon-72x72.png",
            "sizes" => "72x72",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => $basePath . "/assets/images/icons/icon-96x96.png", 
            "sizes" => "96x96",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => $basePath . "/assets/images/icons/icon-128x128.png",
            "sizes" => "128x128", 
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => $basePath . "/assets/images/icons/icon-144x144.png",
            "sizes" => "144x144",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => $basePath . "/assets/images/icons/icon-152x152.png",
            "sizes" => "152x152",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => $basePath . "/assets/images/icons/icon-192x192.png",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any maskable"
        ],
        [
            "src" => $basePath . "/assets/images/icons/icon-384x384.png",
            "sizes" => "384x384", 
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => $basePath . "/assets/images/icons/icon-512x512.png",
            "sizes" => "512x512",
            "type" => "image/png", 
            "purpose" => "any maskable"
        ],
        [
            "src" => $basePath . "/assets/images/icons/maskable-icon-192x192.png",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "maskable"
        ],
        [
            "src" => $basePath . "/assets/images/icons/maskable-icon-512x512.png", 
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "maskable"
        ]
    ],

    "shortcuts" => [
        [
            "name" => "Explorar Tours",
            "short_name" => "Tours", 
            "description" => "Ver todos los tours disponibles",
            "url" => $basePath . "/?route=tours&utm_source=shortcut",
            "icons" => [
                [
                    "src" => $basePath . "/assets/images/icons/shortcut-tours.png",
                    "sizes" => "96x96"
                ]
            ]
        ],
        [
            "name" => "Mis Reservas",
            "short_name" => "Reservas",
            "description" => "Ver mis reservas activas", 
            "url" => $basePath . "/?route=booking/my-bookings&utm_source=shortcut",
            "icons" => [
                [
                    "src" => $basePath . "/assets/images/icons/shortcut-bookings.png", 
                    "sizes" => "96x96"
                ]
            ]
        ],
        [
            "name" => "Wishlist",
            "short_name" => "Favoritos",
            "description" => "Tours guardados como favoritos",
            "url" => $basePath . "/?route=wishlist&utm_source=shortcut", 
            "icons" => [
                [
                    "src" => $basePath . "/assets/images/icons/shortcut-wishlist.png",
                    "sizes" => "96x96"
                ]
            ]
        ],
        [
            "name" => "Chat Soporte", 
            "short_name" => "Chat",
            "description" => "Contactar con nuestro equipo",
            "url" => $basePath . "/?route=chat&utm_source=shortcut",
            "icons" => [
                [
                    "src" => $basePath . "/assets/images/icons/shortcut-chat.png",
                    "sizes" => "96x96" 
                ]
            ]
        ]
    ],

    "launch_handler" => [
        "client_mode" => "focus-existing"
    ],

    "handle_links" => "preferred",

    "permissions" => [
        "notifications",
        "geolocation", 
        "camera",
        "persistent-storage"
    ]
];

// Enviar headers correctos
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache 1 hora

// Enviar JSON
echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
