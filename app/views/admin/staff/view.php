<?php 
use App\Core\Config;
include __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php
      $actionTitle = 'Detalles del Empleado';
      $actionSubtitle = htmlspecialchars($employee['nombre'] . ' ' . $employee['apellido']);
      $actionButtons = [
        ['label' => 'Volver a Personal', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/staff'],
        ['label' => 'Editar', 'icon' => 'fas fa-edit', 'variant' => 'primary', 'href' => Config::getBaseUrl() . '?route=admin/staff/edit/' . $employee['id']],
      ];
      include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <div class="row">
        <!-- Información Personal -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user me-2"></i>Información Personal
                    </h6>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($employee['foto'])): ?>
                        <img src="<?= Config::getBaseUrl() . 'uploads/staff/' . htmlspecialchars($employee['foto']) ?>"
                             alt="Foto" class="img-thumbnail mb-3" style="max-width: 200px;">
                    <?php else: ?>
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>

                    <h4><?= htmlspecialchars($employee['nombre'] . ' ' . $employee['apellido']) ?></h4>

                    <span class="badge bg-<?= $employee['estado'] === 'activo' ? 'success' : ($employee['estado'] === 'suspendido' ? 'danger' : 'secondary') ?> mb-3">
                        <?= ucfirst($employee['estado']) ?>
                    </span>

                    <hr>

                    <div class="text-start">
                        <?php if ($employee['email']): ?>
                        <p class="mb-2">
                            <i class="fas fa-envelope text-muted me-2"></i>
                            <a href="mailto:<?= htmlspecialchars($employee['email']) ?>"><?= htmlspecialchars($employee['email']) ?></a>
                        </p>
                        <?php endif; ?>

                        <p class="mb-2">
                            <i class="fas fa-phone text-muted me-2"></i>
                            <a href="tel:<?= htmlspecialchars($employee['telefono']) ?>"><?= htmlspecialchars($employee['telefono']) ?></a>
                        </p>

                        <?php if ($employee['dpi']): ?>
                        <p class="mb-2">
                            <i class="fas fa-id-card text-muted me-2"></i>
                            <?= htmlspecialchars($employee['dpi']) ?>
                        </p>
                        <?php endif; ?>

                        <?php if ($employee['fecha_nacimiento']): ?>
                        <p class="mb-2">
                            <i class="fas fa-birthday-cake text-muted me-2"></i>
                            <?= date('d/m/Y', strtotime($employee['fecha_nacimiento'])) ?>
                            <?php
                            $edad = date_diff(date_create($employee['fecha_nacimiento']), date_create('now'))->y;
                            echo " ({$edad} años)";
                            ?>
                        </p>
                        <?php endif; ?>

                        <?php if ($employee['direccion']): ?>
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                            <?= nl2br(htmlspecialchars($employee['direccion'])) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Laboral -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-briefcase me-2"></i>Información Laboral
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Tipo de Empleado</label>
                            <p class="mb-0">
                                <span class="badge bg-info"><?= ucfirst(htmlspecialchars($employee['tipo_empleado'])) ?></span>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Puesto</label>
                            <p class="mb-0"><strong><?= htmlspecialchars($employee['puesto']) ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Salario</label>
                            <p class="mb-0">
                                <?= $employee['salario'] ? 'Q ' . number_format($employee['salario'], 2) : 'No especificado' ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Años de Experiencia</label>
                            <p class="mb-0"><?= $employee['experiencia_anios'] ?? 0 ?> años</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Fecha de Contratación</label>
                            <p class="mb-0">
                                <?= $employee['fecha_contratacion'] ? date('d/m/Y', strtotime($employee['fecha_contratacion'])) : 'No especificado' ?>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($employee['idiomas'])): ?>
                    <hr>
                    <div class="mb-3">
                        <label class="text-muted small">Idiomas</label>
                        <p class="mb-0">
                            <?php foreach ($employee['idiomas'] as $idioma): ?>
                                <span class="badge bg-secondary me-1"><?= htmlspecialchars($idioma) ?></span>
                            <?php endforeach; ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($employee['certificaciones'])): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Certificaciones</label>
                        <p class="mb-0">
                            <?php foreach ($employee['certificaciones'] as $cert): ?>
                                <span class="badge bg-success me-1 mb-1"><i class="fas fa-certificate me-1"></i><?= htmlspecialchars($cert) ?></span>
                            <?php endforeach; ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($employee['notas']): ?>
                    <hr>
                    <div>
                        <label class="text-muted small">Notas</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($employee['notas'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="text-muted small">ID</label>
                            <p class="mb-0"><strong>#<?= $employee['id'] ?></strong></p>
                        </div>
                        <?php if ($employee['created_at']): ?>
                        <div class="col-md-4">
                            <label class="text-muted small">Fecha de Creación</label>
                            <p class="mb-0"><?= date('d/m/Y H:i', strtotime($employee['created_at'])) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($employee['updated_at']): ?>
                        <div class="col-md-4">
                            <label class="text-muted small">Última Actualización</label>
                            <p class="mb-0"><?= date('d/m/Y H:i', strtotime($employee['updated_at'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
