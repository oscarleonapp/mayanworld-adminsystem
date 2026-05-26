<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Modelo: BlogPost
 * Gestión de posts del blog con funcionalidades SEO Enterprise
 */
class BlogPost extends Model
{
    protected $table = 'blog_posts';

    protected $fillable = [
        'titulo', 'slug', 'descripcion_corta', 'contenido',
        'imagen_destacada', 'imagen_alt', 'categoria_id', 'autor_id',
        'meta_title', 'meta_description', 'meta_keywords',
        'canonical_url', 'og_image', 'focus_keyword',
        'seo_score', 'readability_score',
        'estado', 'fecha_publicacion', 'destacado',
        'tiempo_lectura', 'vistas'
    ];

    /**
     * Obtener posts publicados con paginación
     *
     * @param int|null $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @return array Lista de posts publicados
     */
    public function getPublished($limit = null, $offset = 0)
    {
        $sql = "
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.color as categoria_color,
                c.icono as categoria_icono,
                u.nombre as autor_nombre
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            LEFT JOIN usuarios u ON p.autor_id = u.id
            WHERE p.estado = 'published'
              AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion <= NOW())
            ORDER BY p.fecha_publicacion DESC, p.created_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            return $this->db->fetchAll($sql, [
                'limit' => $limit,
                'offset' => $offset
            ]);
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener posts por categoría
     *
     * @param int $categoryId ID de la categoría
     * @param int|null $limit Límite de resultados
     * @return array Posts de la categoría
     */
    public function getByCategory($categoryId, $limit = null)
    {
        $sql = "
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.color as categoria_color,
                c.icono as categoria_icono,
                u.nombre as autor_nombre
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            LEFT JOIN usuarios u ON p.autor_id = u.id
            WHERE p.categoria_id = :category_id
              AND p.estado = 'published'
              AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion <= NOW())
            ORDER BY p.fecha_publicacion DESC
        ";

        if ($limit) {
            $sql .= " LIMIT :limit";
            return $this->db->fetchAll($sql, [
                'category_id' => $categoryId,
                'limit' => $limit
            ]);
        }

        return $this->db->fetchAll($sql, ['category_id' => $categoryId]);
    }

    /**
     * Obtener posts destacados
     *
     * @param int $limit Límite de resultados (default: 6)
     * @return array Posts destacados
     */
    public function getFeatured($limit = 6)
    {
        return $this->db->fetchAll("
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.color as categoria_color,
                c.icono as categoria_icono,
                u.nombre as autor_nombre
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            LEFT JOIN usuarios u ON p.autor_id = u.id
            WHERE p.destacado = 1
              AND p.estado = 'published'
              AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion <= NOW())
            ORDER BY p.fecha_publicacion DESC
            LIMIT :limit
        ", ['limit' => $limit]);
    }

    /**
     * Obtener posts recientes
     *
     * @param int $limit Límite de resultados (default: 5)
     * @return array Posts recientes
     */
    public function getRecent($limit = 5)
    {
        return $this->db->fetchAll("
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            WHERE p.estado = 'published'
              AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion <= NOW())
            ORDER BY p.fecha_publicacion DESC, p.created_at DESC
            LIMIT :limit
        ", ['limit' => $limit]);
    }

    /**
     * Obtener posts relacionados por categoría
     *
     * @param int $postId ID del post actual (para excluirlo)
     * @param int $categoryId ID de la categoría
     * @param int $limit Límite de resultados (default: 3)
     * @return array Posts relacionados
     */
    public function getRelated($postId, $categoryId, $limit = 3)
    {
        return $this->db->fetchAll("
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.color as categoria_color,
                c.icono as categoria_icono
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            WHERE p.categoria_id = :category_id
              AND p.id != :post_id
              AND p.estado = 'published'
              AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion <= NOW())
            ORDER BY p.fecha_publicacion DESC
            LIMIT :limit
        ", [
            'category_id' => $categoryId,
            'post_id' => $postId,
            'limit' => $limit
        ]);
    }

    /**
     * Buscar posts por término
     *
     * @param string $term Término de búsqueda
     * @param int|null $limit Límite de resultados
     * @return array Posts encontrados
     */
    public function searchPosts($term, $limit = null)
    {
        $sql = "
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                MATCH(p.titulo, p.contenido) AGAINST(:term) as relevancia
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            WHERE p.estado = 'published'
              AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion <= NOW())
              AND (
                  p.titulo LIKE :like_term
                  OR p.contenido LIKE :like_term
                  OR p.meta_keywords LIKE :like_term
              )
            ORDER BY relevancia DESC, p.fecha_publicacion DESC
        ";

        if ($limit) {
            $sql .= " LIMIT :limit";
        }

        $likeTerm = '%' . $term . '%';
        $params = [
            'term' => $term,
            'like_term' => $likeTerm
        ];

        if ($limit) {
            $params['limit'] = $limit;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtener posts por archivo (año y opcionalmente mes)
     *
     * @param int $year Año
     * @param int|null $month Mes (opcional)
     * @return array Posts del archivo
     */
    public function getArchived($year, $month = null)
    {
        $sql = "
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            WHERE p.estado = 'published'
              AND YEAR(p.fecha_publicacion) = :year
        ";

        $params = ['year' => $year];

        if ($month !== null) {
            $sql .= " AND MONTH(p.fecha_publicacion) = :month";
            $params['month'] = $month;
        }

        $sql .= " ORDER BY p.fecha_publicacion DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtener post por slug (para URLs SEO-friendly)
     *
     * @param string $slug Slug del post
     * @return array|null Post encontrado o null
     */
    public function getBySlug($slug)
    {
        return $this->db->fetchOne("
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.color as categoria_color,
                c.icono as categoria_icono,
                u.nombre as autor_nombre,
                u.email as autor_email
            FROM {$this->table} p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            LEFT JOIN usuarios u ON p.autor_id = u.id
            WHERE p.slug = :slug
        ", ['slug' => $slug]);
    }

    /**
     * Incrementar contador de vistas
     *
     * @param int $postId ID del post
     * @return bool Éxito de la operación
     */
    public function incrementViews($postId)
    {
        return $this->db->update(
            $this->table,
            ['vistas' => 'vistas + 1'],
            'id = :id',
            ['id' => $postId]
        );
    }

    /**
     * Calcular tiempo de lectura basado en contenido
     * Asume 200 palabras por minuto (promedio adulto)
     *
     * @param string $content Contenido HTML
     * @return int Tiempo en minutos
     */
    public function calculateReadingTime($content)
    {
        // Remover tags HTML
        $text = strip_tags($content);

        // Contar palabras
        $wordCount = str_word_count($text);

        // Calcular minutos (200 palabras/minuto)
        $minutes = ceil($wordCount / 200);

        return max(1, $minutes); // Mínimo 1 minuto
    }

    /**
     * Calcular puntuación SEO basada en múltiples factores
     *
     * @param array $data Datos del post
     * @return array ['score' => int, 'checks' => array, 'suggestions' => array]
     */
    public function calculateSeoScore($data)
    {
        $score = 0;
        $checks = [];
        $suggestions = [];
        $maxScore = 100;

        // 1. Título contiene focus keyword (15 puntos)
        if (!empty($data['focus_keyword']) && !empty($data['titulo'])) {
            if (stripos($data['titulo'], $data['focus_keyword']) !== false) {
                $score += 15;
                $checks[] = '✅ Título contiene palabra clave principal';
            } else {
                $suggestions[] = 'Incluye la palabra clave "' . $data['focus_keyword'] . '" en el título';
            }
        }

        // 2. Meta título óptimo (10 puntos)
        $metaTitleLength = mb_strlen($data['meta_title'] ?? '');
        if ($metaTitleLength >= 50 && $metaTitleLength <= 60) {
            $score += 10;
            $checks[] = '✅ Meta título tiene longitud óptima (50-60 caracteres)';
        } else {
            $suggestions[] = 'Ajusta el meta título a 50-60 caracteres (actual: ' . $metaTitleLength . ')';
        }

        // 3. Meta descripción óptima (10 puntos)
        $metaDescLength = mb_strlen($data['meta_description'] ?? '');
        if ($metaDescLength >= 150 && $metaDescLength <= 160) {
            $score += 10;
            $checks[] = '✅ Meta descripción tiene longitud óptima (150-160 caracteres)';
        } else {
            $suggestions[] = 'Ajusta la meta descripción a 150-160 caracteres (actual: ' . $metaDescLength . ')';
        }

        // 4. URL amigable (slug) (10 puntos)
        if (!empty($data['slug']) && preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            $score += 10;
            $checks[] = '✅ URL es SEO-friendly (solo minúsculas, números y guiones)';
        } else {
            $suggestions[] = 'Asegúrate de que el slug solo contenga minúsculas, números y guiones';
        }

        // 5. Imagen destacada con alt text (10 puntos)
        if (!empty($data['imagen_destacada']) && !empty($data['imagen_alt'])) {
            $score += 10;
            $checks[] = '✅ Imagen destacada tiene texto alternativo';
        } else {
            $suggestions[] = 'Agrega una imagen destacada con texto alternativo descriptivo';
        }

        // 6. Longitud del contenido (15 puntos)
        $wordCount = str_word_count(strip_tags($data['contenido'] ?? ''));
        if ($wordCount >= 300) {
            $score += 15;
            $checks[] = '✅ Contenido tiene suficientes palabras (' . $wordCount . ' palabras)';
        } else {
            $suggestions[] = 'El contenido debería tener al menos 300 palabras (actual: ' . $wordCount . ')';
        }

        // 7. Uso de encabezados H2/H3 (10 puntos)
        $headingCount = preg_match_all('/<h[23]>/i', $data['contenido'] ?? '', $matches);
        if ($headingCount >= 2) {
            $score += 10;
            $checks[] = '✅ Contenido usa encabezados jerárquicos (H2/H3)';
        } else {
            $suggestions[] = 'Agrega al menos 2 encabezados H2 o H3 para mejor estructura';
        }

        // 8. Densidad de palabra clave (10 puntos)
        if (!empty($data['focus_keyword'])) {
            $density = $this->calculateKeywordDensity($data['contenido'] ?? '', $data['focus_keyword']);
            if ($density >= 1 && $density <= 3) {
                $score += 10;
                $checks[] = '✅ Densidad de palabra clave es óptima (' . number_format($density, 1) . '%)';
            } else {
                $suggestions[] = 'Ajusta la densidad de palabra clave a 1-3% (actual: ' . number_format($density, 1) . '%)';
            }
        }

        // 9. Enlaces internos (5 puntos)
        $internalLinks = preg_match_all('/href=["\'](?!http|#)/i', $data['contenido'] ?? '', $matches);
        if ($internalLinks >= 2) {
            $score += 5;
            $checks[] = '✅ Contenido incluye enlaces internos';
        } else {
            $suggestions[] = 'Agrega al menos 2 enlaces internos a otros contenidos';
        }

        // 10. Canonical URL configurada (5 puntos)
        if (!empty($data['canonical_url'])) {
            $score += 5;
            $checks[] = '✅ URL canónica configurada';
        }

        return [
            'score' => min($score, $maxScore),
            'checks' => $checks,
            'suggestions' => $suggestions,
            'max_score' => $maxScore
        ];
    }

    /**
     * Calcular densidad de palabra clave
     *
     * @param string $content Contenido
     * @param string $keyword Palabra clave
     * @return float Densidad en porcentaje
     */
    private function calculateKeywordDensity($content, $keyword)
    {
        $text = strip_tags($content);
        $totalWords = str_word_count($text);

        if ($totalWords === 0) {
            return 0;
        }

        $keywordCount = substr_count(strtolower($text), strtolower($keyword));

        return ($keywordCount / $totalWords) * 100;
    }

    /**
     * Calcular puntuación de legibilidad (adaptación del Flesch Reading Ease al español)
     *
     * @param string $content Contenido HTML
     * @return array ['score' => int, 'level' => string, 'suggestions' => array]
     */
    public function calculateReadabilityScore($content)
    {
        $text = strip_tags($content);

        // Contar oraciones (aproximación: puntos, signos de exclamación, interrogación)
        $sentenceCount = preg_match_all('/[.!?]+/', $text, $matches);
        if ($sentenceCount === 0) $sentenceCount = 1;

        // Contar palabras
        $wordCount = str_word_count($text);
        if ($wordCount === 0) {
            return [
                'score' => 0,
                'level' => 'Sin contenido',
                'avg_words_per_sentence' => 0,
                'suggestions' => ['Agrega contenido al artículo']
            ];
        }

        // Contar sílabas (aproximación para español)
        $syllableCount = $this->estimateSyllables($text);

        // Fórmula Flesch adaptada: 206.835 - 1.015(palabras/oraciones) - 84.6(sílabas/palabras)
        $avgWordsPerSentence = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $syllableCount / $wordCount;

        $score = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord);
        $score = max(0, min(100, $score)); // Limitar entre 0-100

        // Determinar nivel
        if ($score >= 80) {
            $level = 'Muy fácil';
        } elseif ($score >= 60) {
            $level = 'Fácil';
        } elseif ($score >= 50) {
            $level = 'Medio';
        } elseif ($score >= 30) {
            $level = 'Difícil';
        } else {
            $level = 'Muy difícil';
        }

        // Sugerencias
        $suggestions = [];
        if ($avgWordsPerSentence > 20) {
            $suggestions[] = 'Reduce el promedio de palabras por oración (actual: ' . round($avgWordsPerSentence, 1) . ')';
        }
        if ($score < 60) {
            $suggestions[] = 'Usa oraciones más cortas y palabras más simples para mejorar legibilidad';
        }

        return [
            'score' => round($score),
            'level' => $level,
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'suggestions' => $suggestions
        ];
    }

    /**
     * Estimar sílabas en texto español (aproximación)
     *
     * @param string $text Texto
     * @return int Número estimado de sílabas
     */
    private function estimateSyllables($text)
    {
        // Vocales en español
        $vowels = ['a', 'e', 'i', 'o', 'u', 'á', 'é', 'í', 'ó', 'ú', 'ü'];

        $text = strtolower($text);
        $syllables = 0;

        $words = str_word_count($text, 1);

        foreach ($words as $word) {
            $wordSyllables = 0;
            $previousWasVowel = false;

            for ($i = 0; $i < mb_strlen($word); $i++) {
                $char = mb_substr($word, $i, 1);
                $isVowel = in_array($char, $vowels);

                if ($isVowel && !$previousWasVowel) {
                    $wordSyllables++;
                }

                $previousWasVowel = $isVowel;
            }

            // Mínimo 1 sílaba por palabra
            $syllables += max(1, $wordSyllables);
        }

        return $syllables;
    }

    /**
     * Validar datos del post
     *
     * @param array $data Datos a validar
     * @param bool $isUpdate Si es actualización (ID no requerido)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePost($data, $isUpdate = false)
    {
        $errors = [];

        // Validar título
        if (empty($data['titulo'])) {
            $errors[] = 'El título es obligatorio';
        } elseif (mb_strlen($data['titulo']) > 255) {
            $errors[] = 'El título no debe exceder 255 caracteres';
        }

        // Validar slug
        if (empty($data['slug'])) {
            $errors[] = 'El slug es obligatorio';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            $errors[] = 'El slug solo debe contener letras minúsculas, números y guiones';
        } else {
            // Verificar slug único
            $existing = $this->db->fetchOne(
                "SELECT id FROM {$this->table} WHERE slug = :slug" . ($isUpdate ? " AND id != :id" : ""),
                $isUpdate ? ['slug' => $data['slug'], 'id' => $data['id']] : ['slug' => $data['slug']]
            );

            if ($existing) {
                $errors[] = 'Ya existe un post con este slug';
            }
        }

        // Validar contenido
        if (empty($data['contenido'])) {
            $errors[] = 'El contenido es obligatorio';
        }

        // Validar autor
        if (empty($data['autor_id'])) {
            $errors[] = 'El autor es obligatorio';
        }

        // Validar estado
        $validEstados = ['draft', 'published', 'scheduled'];
        if (!empty($data['estado']) && !in_array($data['estado'], $validEstados)) {
            $errors[] = 'Estado inválido';
        }

        // Validar meta fields lengths
        if (!empty($data['meta_title']) && mb_strlen($data['meta_title']) > 255) {
            $errors[] = 'El meta título no debe exceder 255 caracteres';
        }

        if (!empty($data['meta_keywords']) && mb_strlen($data['meta_keywords']) > 500) {
            $errors[] = 'Las meta keywords no deben exceder 500 caracteres';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obtener archivo de posts agrupado por mes/año
     *
     * @return array Meses con posts disponibles
     */
    public function getArchiveMonths()
    {
        return $this->db->fetchAll("
            SELECT
                YEAR(fecha_publicacion) as year,
                MONTH(fecha_publicacion) as month,
                COUNT(*) as post_count,
                DATE_FORMAT(fecha_publicacion, '%Y-%m-01') as date
            FROM {$this->table}
            WHERE estado = 'published'
              AND fecha_publicacion IS NOT NULL
            GROUP BY year, month
            ORDER BY year DESC, month DESC
        ");
    }

    /**
     * Obtener tags de un post
     *
     * @param int $postId ID del post
     * @return array Tags del post
     */
    public function getTags($postId)
    {
        return $this->db->fetchAll("
            SELECT t.*
            FROM blog_tags t
            INNER JOIN blog_post_tags pt ON t.id = pt.tag_id
            WHERE pt.post_id = :post_id
            ORDER BY t.nombre
        ", ['post_id' => $postId]);
    }

    /**
     * Sincronizar tags de un post
     *
     * @param int $postId ID del post
     * @param array $tagIds Array de IDs de tags
     * @return bool Éxito
     */
    public function syncTags($postId, $tagIds)
    {
        // Eliminar tags actuales
        $this->db->query("DELETE FROM blog_post_tags WHERE post_id = :post_id", ['post_id' => $postId]);

        // Insertar nuevos tags
        if (!empty($tagIds)) {
            foreach ($tagIds as $tagId) {
                $this->db->insert('blog_post_tags', [
                    'post_id' => $postId,
                    'tag_id' => $tagId
                ]);
            }
        }

        return true;
    }
}
