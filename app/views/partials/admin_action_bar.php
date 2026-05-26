<?php
// Variables esperadas:
// $actionTitle (string), $actionSubtitle (string|null)
// $actionButtons (array): cada item = [
//   'label' => 'Texto',
//   'icon' => 'fas fa-plus',
//   'variant' => 'primary' | 'outline-secondary' | etc,
//   'href' => '...'
// ] o con 'onclick' => 'func()' en lugar de href.
?>
<div class="admin-actions admin-hero mb-4">
  <div class="admin-actions__meta admin-hero__content">
    <?php $actionKicker = $actionKicker ?? 'Panel administrativo'; ?>
    <div class="admin-hero__kicker"><?= htmlspecialchars($actionKicker) ?></div>
    <h1 class="admin-hero__title mb-0"><?= htmlspecialchars($actionTitle) ?></h1>
    <?php if (!empty($actionSubtitle)): ?>
      <p class="admin-hero__subtitle mb-0"><?= htmlspecialchars($actionSubtitle) ?></p>
    <?php endif; ?>
    <?php if (!empty($actionMeta) && is_array($actionMeta)): ?>
      <div class="admin-hero__meta">
        <?php foreach ($actionMeta as $meta): ?>
          <span class="meta-pill">
            <?php if (!empty($meta['icon'])): ?>
              <i class="<?= htmlspecialchars($meta['icon']) ?>"></i>
            <?php endif; ?>
            <?= htmlspecialchars($meta['label'] ?? '') ?>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="admin-actions__buttons admin-hero__actions">
    <?php foreach ($actionButtons ?? [] as $btn): ?>
      <?php $variant = $btn['variant'] ?? 'primary'; ?>
      <?php $icon = !empty($btn['icon']) ? '<i class="' . htmlspecialchars($btn['icon']) . ' me-2"></i>' : ''; ?>
      <?php $badge = isset($btn['badge']) ? (string)$btn['badge'] : null; ?>
      <?php $badgeClass = $btn['badgeClass'] ?? 'bg-secondary'; ?>
      <?php $btnId = !empty($btn['id']) ? 'id="' . htmlspecialchars($btn['id']) . '"' : ''; ?>
      <?php
        $extraAttrs = '';
        if (!empty($btn['attributes'])) {
          if (is_array($btn['attributes'])) {
            foreach ($btn['attributes'] as $attr => $value) {
              $extraAttrs .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
            }
          } else {
            $extraAttrs = ' ' . $btn['attributes'];
          }
        }
      ?>
      <?php if (!empty($btn['href'])): ?>
        <a href="<?= $btn['href'] ?>" class="btn btn-<?= htmlspecialchars($variant) ?>" <?= $btnId ?><?= $extraAttrs ?>>
          <?= $icon ?><?= htmlspecialchars($btn['label']) ?><?php if ($badge !== null): ?> <span class="badge btn-badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($badge) ?></span><?php endif; ?>
        </a>
      <?php else: ?>
        <button class="btn btn-<?= htmlspecialchars($variant) ?>" <?= $btnId ?><?= $extraAttrs ?> <?= !empty($btn['onclick']) ? 'onclick="' . htmlspecialchars($btn['onclick']) . '"' : '' ?>>
          <?= $icon ?><?= htmlspecialchars($btn['label']) ?><?php if ($badge !== null): ?> <span class="badge btn-badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($badge) ?></span><?php endif; ?>
        </button>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
