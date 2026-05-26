<?php
namespace App\Helpers;

use App\Models\FooterSection;
use App\Core\Config;
use App\Core\Helpers;

/**
 * FooterHelper
 *
 * Helper para renderizar el footer dinámico desde la base de datos
 * Soporta múltiples tipos de secciones y configuración visual
 *
 * @version 1.0.0
 * @date 2025-11-05
 */
class FooterHelper
{
    private static $model = null;
    private static $initialized = false;

    /**
     * Inicializar el helper
     */
    private static function init()
    {
        if (self::$initialized) {
            return;
        }

        self::$model = new FooterSection();
        self::$initialized = true;
    }

    /**
     * Renderizar el footer completo
     *
     * @param array $options Opciones de renderizado
     * @return string HTML del footer
     */
    public static function renderFooter($options = [])
    {
        self::init();

        // Verificar si el footer dinámico está habilitado
        if (!self::$model->isEnabled()) {
            return self::renderLegacyFooter();
        }

        $defaults = [
            'container_class' => 'container',
            'row_class' => 'row',
            'show_copyright' => true,
            'show_bottom_links' => true
        ];

        $options = array_merge($defaults, $options);

        // Obtener configuración global
        $config = self::$model->getFooterConfig();
        $numColumns = isset($config['num_columns']) ? (int)$config['num_columns'] : 4;

        // Obtener secciones agrupadas por columna
        $sectionsByColumn = self::$model->getSectionsByColumn();

        // Calcular clase de columnas Bootstrap
        $colClass = self::getColumnClass($numColumns);

        ob_start();
        ?>
        <footer class="site-footer py-4" style="background-color: <?= htmlspecialchars($config['bg_color'] ?? '#f8f9fa') ?>; color: <?= htmlspecialchars($config['text_color'] ?? '#6c757d') ?>;">
            <div class="<?= htmlspecialchars($options['container_class']) ?>">
                <div class="<?= htmlspecialchars($options['row_class']) ?>">
                    <?php
                    // Renderizar cada columna
                    for ($col = 1; $col <= $numColumns; $col++):
                        if (isset($sectionsByColumn[$col]) && count($sectionsByColumn[$col]) > 0):
                    ?>
                        <div class="<?= $colClass ?>">
                            <?php
                            foreach ($sectionsByColumn[$col] as $section) {
                                echo self::renderSection($section);
                            }
                            ?>
                        </div>
                    <?php
                        endif;
                    endfor;
                    ?>
                </div>

                <?php if ($options['show_copyright'] || $options['show_bottom_links']): ?>
                <hr class="my-4">
                <div class="row align-items-center">
                    <?php if ($options['show_copyright']): ?>
                    <div class="col-md-6">
                        <?= self::renderCopyright($config) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($options['show_bottom_links'] && ($config['show_bottom_links'] ?? 'yes') === 'yes'): ?>
                    <div class="col-md-6 text-md-end footer-links">
                        <?= self::renderBottomLinks($config) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </footer>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar una sección individual
     *
     * @param array $section Datos de la sección
     * @return string HTML de la sección
     */
    private static function renderSection($section)
    {
        $content = $section['content_decoded'];

        switch ($section['type']) {
            case FooterSection::TYPE_COMPANY_INFO:
                return self::renderCompanyInfo($section, $content);

            case FooterSection::TYPE_LINKS:
                return self::renderLinks($section, $content);

            case FooterSection::TYPE_CONTACT:
                return self::renderContact($section, $content);

            case FooterSection::TYPE_SOCIAL:
                return self::renderSocial($section, $content);

            case FooterSection::TYPE_NEWSLETTER:
                return self::renderNewsletter($section, $content);

            case FooterSection::TYPE_CUSTOM:
                return self::renderCustom($section, $content);

            default:
                return '';
        }
    }

    /**
     * Renderizar sección de información de la empresa
     */
    private static function renderCompanyInfo($section, $content)
    {
        $useCompanyConfig = $content['use_company_config'] ?? true;

        if (!$useCompanyConfig) {
            return '';
        }

        $companyName = CompanyConfigHelper::get('company_name', 'Travel Mayan World');
        $companyTagline = CompanyConfigHelper::get('company_tagline', 'Tu agencia de viajes de confianza');
        $showLogo = $content['show_logo'] ?? false;
        $showName = $content['show_name'] ?? true;
        $showTagline = $content['show_tagline'] ?? true;

        ob_start();
        ?>
        <div class="footer-company-info">
            <?php if ($showName): ?>
            <h5>
                <?php if ($showLogo): ?>
                <i class="fas fa-plane me-2"></i>
                <?php endif; ?>
                <?= htmlspecialchars($companyName) ?>
            </h5>
            <?php endif; ?>

            <?php if ($showTagline): ?>
            <p class="text-muted"><?= htmlspecialchars($companyTagline) ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar sección de enlaces
     */
    private static function renderLinks($section, $content)
    {
        $title = $section['title'] ?? '';
        ob_start();
        ?>
        <div class="footer-links-section">
            <h6><?= htmlspecialchars($title) ?></h6>
            <?php
            $source = $content['source'] ?? 'navigation_menu';

            if ($source === 'navigation_menu') {
                // Usar NavigationHelper para renderizar menú
                $menuName = $content['menu_name'] ?? 'footer';
                $showIcons = $content['show_icons'] ?? true;

                echo NavigationHelper::renderMenu($menuName, [
                    'class' => 'list-unstyled',
                    'item_class' => 'mb-2',
                    'link_class' => 'text-muted text-decoration-none',
                    'show_icons' => $showIcons,
                    'format' => 'list'
                ]);
            } elseif ($source === 'custom' && isset($content['links'])) {
                // Enlaces personalizados
                echo '<ul class="list-unstyled">';
                foreach ($content['links'] as $link) {
                    $label = $link['label'] ?? '';
                    echo '<li class="mb-2">';
                    echo '<a href="' . htmlspecialchars($link['url']) . '" class="text-muted text-decoration-none">';
                    if (!empty($link['icon'])) {
                        echo '<i class="' . htmlspecialchars($link['icon']) . ' me-2"></i>';
                    }
                    echo htmlspecialchars($label);
                    echo '</a>';
                    echo '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar sección de contacto
     */
    private static function renderContact($section, $content)
    {
        $useCompanyConfig = $content['use_company_config'] ?? true;
        $showFields = $content['show_fields'] ?? ['email', 'phone', 'address', 'whatsapp'];
        $showIcons = $content['show_icons'] ?? true;

        if (!$useCompanyConfig) {
            return '';
        }

        $contactInfo = CompanyConfigHelper::getContactInfo();

        $title = $section['title'] ?? '';
        ob_start();
        ?>
        <div class="footer-contact-section">
            <h6><?= htmlspecialchars($title) ?></h6>

            <?php if (in_array('email', $showFields) && !empty($contactInfo['email'])): ?>
            <p class="text-muted mb-1">
                <?php if ($showIcons): ?><i class="fas fa-envelope me-2"></i><?php endif; ?>
                <a href="<?= CompanyConfigHelper::getEmailLink() ?>" class="text-muted text-decoration-none">
                    <?= htmlspecialchars($contactInfo['email']) ?>
                </a>
            </p>
            <?php endif; ?>

            <?php if (in_array('phone', $showFields) && !empty($contactInfo['phone'])): ?>
            <p class="text-muted mb-1">
                <?php if ($showIcons): ?><i class="fas fa-phone me-2"></i><?php endif; ?>
                <a href="<?= CompanyConfigHelper::getPhoneLink() ?>" class="text-muted text-decoration-none">
                    <?= htmlspecialchars($contactInfo['phone']) ?>
                </a>
            </p>
            <?php endif; ?>

            <?php if (in_array('address', $showFields) && !empty($contactInfo['address'])): ?>
            <p class="text-muted mb-1">
                <?php if ($showIcons): ?><i class="fas fa-map-marker-alt me-2"></i><?php endif; ?>
                <?= htmlspecialchars($contactInfo['address']) ?>
            </p>
            <?php endif; ?>

            <?php if (in_array('whatsapp', $showFields) && !empty($contactInfo['whatsapp'])): ?>
            <p class="text-muted">
                <?php if ($showIcons): ?><i class="fab fa-whatsapp me-2"></i><?php endif; ?>
                <a href="<?= CompanyConfigHelper::getWhatsAppLink('Hola, quiero más información') ?>"
                   target="_blank"
                   class="text-muted text-decoration-none">
                    <?= htmlspecialchars($contactInfo['whatsapp']) ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar sección de redes sociales
     */
    private static function renderSocial($section, $content)
    {
        $useCompanyConfig = $content['use_company_config'] ?? true;
        $platforms = $content['platforms'] ?? ['facebook', 'instagram', 'twitter', 'youtube', 'whatsapp'];
        $iconSize = $content['icon_size'] ?? 'fa-lg';
        $layout = $content['layout'] ?? 'horizontal';

        if (!$useCompanyConfig) {
            return '';
        }

        $socialMedia = CompanyConfigHelper::getSocialMedia();
        $contactInfo = CompanyConfigHelper::getContactInfo();

        $title = $section['title'] ?? '';
        ob_start();
        ?>
        <div class="footer-social-section">
            <h6><?= htmlspecialchars($title) ?></h6>
            <div class="d-flex gap-3 footer-social <?= $layout === 'vertical' ? 'flex-column' : '' ?>">
                <?php if (in_array('facebook', $platforms) && !empty($socialMedia['facebook'])): ?>
                <a href="<?= htmlspecialchars($socialMedia['facebook']) ?>" target="_blank" aria-label="Facebook">
                    <i class="fab fa-facebook <?= $iconSize ?>"></i>
                </a>
                <?php endif; ?>

                <?php if (in_array('instagram', $platforms) && !empty($socialMedia['instagram'])): ?>
                <a href="<?= htmlspecialchars($socialMedia['instagram']) ?>" target="_blank" aria-label="Instagram">
                    <i class="fab fa-instagram <?= $iconSize ?>"></i>
                </a>
                <?php endif; ?>

                <?php if (in_array('twitter', $platforms) && !empty($socialMedia['twitter'])): ?>
                <a href="<?= htmlspecialchars($socialMedia['twitter']) ?>" target="_blank" aria-label="Twitter">
                    <i class="fab fa-twitter <?= $iconSize ?>"></i>
                </a>
                <?php endif; ?>

                <?php if (in_array('youtube', $platforms) && !empty($socialMedia['youtube'])): ?>
                <a href="<?= htmlspecialchars($socialMedia['youtube']) ?>" target="_blank" aria-label="YouTube">
                    <i class="fab fa-youtube <?= $iconSize ?>"></i>
                </a>
                <?php endif; ?>

                <?php if (in_array('whatsapp', $platforms) && !empty($contactInfo['whatsapp'])): ?>
                <a href="<?= CompanyConfigHelper::getWhatsAppLink('Hola, quiero más información') ?>"
                   target="_blank"
                   aria-label="WhatsApp">
                    <i class="fab fa-whatsapp <?= $iconSize ?>"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar sección de newsletter
     */
    private static function renderNewsletter($section, $content)
    {
        $title = $content['title'] ?? 'Newsletter';
        $description = $content['description'] ?? 'Recibe ofertas exclusivas';
        $buttonText = $content['button_text'] ?? 'Suscribirse';
        $placeholder = $content['placeholder'] ?? 'Tu email';

        ob_start();
        ?>
        <div class="footer-newsletter-section">
            <h6><?= htmlspecialchars($title) ?></h6>
            <p class="text-muted small"><?= htmlspecialchars($description) ?></p>
            <form action="<?= Config::getBaseUrl() ?>?route=newsletter/subscribe" method="POST" class="newsletter-form">
                <div class="input-group input-group-sm">
                    <input type="email"
                           name="email"
                           class="form-control"
                           placeholder="<?= htmlspecialchars($placeholder) ?>"
                           required>
                    <button class="btn btn-primary" type="submit">
                        <?= htmlspecialchars($buttonText) ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar sección personalizada
     */
    private static function renderCustom($section, $content)
    {
        $html = $content['html'] ?? '';

        ob_start();
        ?>
        <div class="footer-custom-section">
            <?php if (!empty($section['title'])): ?>
            <h6><?= htmlspecialchars($section['title']) ?></h6>
            <?php endif; ?>
            <?= $html ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar texto de copyright
     */
    private static function renderCopyright($config)
    {
        $defaultCopyright = "\u{00A9} {year} {company_name}. Todos los derechos reservados.";
        $copyrightText = $config['copyright_text'] ?? $defaultCopyright;
        $companyName = CompanyConfigHelper::get('company_name', 'Travel Mayan World');

        // Reemplazar variables
        $copyrightText = str_replace('{year}', date('Y'), $copyrightText);
        $copyrightText = str_replace('{company_name}', $companyName, $copyrightText);

        return '<p class="text-muted mb-0">' . htmlspecialchars($copyrightText) . '</p>';
    }

    /**
     * Renderizar enlaces inferiores
     */
    private static function renderBottomLinks($config)
    {
        $linksText = $config['bottom_links_text'] ?? "Destinos|Pol\u{00ED}tica de Privacidad|T\u{00E9}rminos y Condiciones";
        $linksUrls = $config['bottom_links_urls'] ?? '?route=destinations|?route=privacy|?route=terms';
        $showAdminLink = ($config['show_admin_link'] ?? 'yes') === 'yes';

        $texts = explode('|', $linksText);
        $urls = explode('|', $linksUrls);

        $baseUrl = Config::getBaseUrl();
        $html = '';

        foreach ($texts as $index => $text) {
            if (isset($urls[$index])) {
                $url = $urls[$index];
                // Agregar baseUrl si la URL no es absoluta
                if (!str_starts_with($url, 'http') && !str_starts_with($url, $baseUrl)) {
                    $url = $baseUrl . $url;
                }
                $label = trim($text);
                $html .= '<a href="' . htmlspecialchars($url) . '" class="me-3">' . htmlspecialchars($label) . '</a>';
            }
        }

        if ($showAdminLink) {
            $html .= '<span class="text-muted">|</span>';
            $html .= '<a href="' . $baseUrl . '?route=admin/login" class="ms-3 text-muted" style="font-size: 0.85rem;">';
            $html .= '<i class="fas fa-shield-alt me-1"></i>Admin';
            $html .= '</a>';
        }

        return $html;
    }

    /**
     * Renderizar footer legacy (fallback)
     */
    private static function renderLegacyFooter()
    {
        // Incluir footer original
        ob_start();
        require __DIR__ . '/../views/layouts/footer_legacy.php';
        return ob_get_clean();
    }

    /**
     * Obtener clase de columna Bootstrap según número de columnas
     */
    private static function getColumnClass($numColumns)
    {
        switch ($numColumns) {
            case 1:
                return 'col-12';
            case 2:
                return 'col-md-6';
            case 3:
                return 'col-md-4';
            case 4:
            default:
                return 'col-md-3';
        }
    }
}
