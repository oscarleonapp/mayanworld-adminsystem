<?php
use App\Core\Config;
use App\Core\Helpers;

$title = $title ?? 'Panel de Chat';
$stats = $stats ?? [];
$pending_conversations = $pending_conversations ?? [];
$active_agents = $active_agents ?? [];
$csrf_token = $csrf_token ?? '';

$flash_message = Helpers::getFlashMessage();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="<?= Helpers::asset('css/admin-premium.css') ?>" rel="stylesheet">
    <style>
        :root {
            --chat-primary: #25d366;
            --chat-secondary: #128c7e;
            --status-online: #4caf50;
            --status-busy: #ff9800;
            --status-away: #ffc107;
            --status-offline: #9e9e9e;
        }

        .chat-dashboard {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--chat-primary);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--chat-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        .stat-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .stat-change.positive { color: #28a745; }
        .stat-change.negative { color: #dc3545; }

        .conversations-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .panel-header {
            background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-secondary) 100%);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .panel-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .conversation-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background-color 0.2s ease;
            position: relative;
        }

        .conversation-item:hover {
            background-color: #f8f9fa;
        }

        .conversation-item:last-child {
            border-bottom: none;
        }

        .conversation-header {
            display: flex;
            justify-content: between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .customer-info {
            flex: 1;
        }

        .customer-name {
            font-weight: 600;
            color: #495057;
            margin: 0;
        }

        .customer-phone {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .conversation-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .priority-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high { background: #fee2e2; color: #dc2626; }
        .priority-normal { background: #f3f4f6; color: #6b7280; }
        .priority-low { background: #dbeafe; color: #2563eb; }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-waiting { background: #fecaca; color: #991b1b; }

        .unread-count {
            background: var(--chat-primary);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .last-message {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0.5rem 0 0 0;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .agents-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .agent-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .agent-item:last-child {
            border-bottom: none;
        }

        .agent-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6c757d;
        }

        .agent-info {
            flex: 1;
        }

        .agent-name {
            font-weight: 600;
            margin: 0;
        }

        .agent-status {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.2rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.online { background: var(--status-online); }
        .status-dot.busy { background: var(--status-busy); }
        .status-dot.away { background: var(--status-away); }
        .status-dot.offline { background: var(--status-offline); }

        .agent-workload {
            display: flex;
            flex-direction: column;
            align-items: end;
            gap: 0.2rem;
        }

        .chat-count {
            background: #e9ecef;
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .specialization-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.1rem 0.4rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn.primary {
            background: var(--chat-primary);
            color: white;
        }

        .action-btn.secondary {
            background: #6c757d;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .real-time-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--chat-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .pulse {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .response-time-chart {
            height: 200px;
            margin-top: 1rem;
        }

        .notification-toast {
            position: fixed;
            top: 80px;
            right: 20px;
            background: white;
            border: 1px solid #e9ecef;
            border-left: 4px solid var(--chat-primary);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 350px;
            z-index: 1050;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .notification-toast.show {
            transform: translateX(0);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .conversation-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body class="legacy-theme">
    <div class="chat-dashboard">
        <!-- Indicador de tiempo real -->
        <div class="real-time-indicator" id="realTimeIndicator">
            <div class="pulse"></div>
            <span>Chat en vivo</span>
        </div>

        <div class="container-fluid">
            <?php
                $actionTitle = 'Panel de Chat WhatsApp Business';
                $actionSubtitle = 'Gestión de conversaciones y agentes en tiempo real';
                $actionButtons = [
                    ['label' => 'Configurar WhatsApp', 'icon' => 'fab fa-whatsapp', 'variant' => 'outline-primary', 'href' => Config::getBaseUrl() . '?route=admin/chat/whatsapp-config'],
                    ['label' => 'Interfaz de Agente', 'icon' => 'fas fa-headset', 'variant' => 'success', 'href' => Config::getBaseUrl() . '?route=admin/chat/agent-interface'],
                    ['label' => 'Actualizar', 'icon' => 'fas fa-refresh', 'variant' => 'info', 'onclick' => 'refreshDashboard()'],
                ];
                include __DIR__ . '/../../partials/admin_action_bar.php';
            ?>

            <!-- Flash messages -->
            <?php if ($flash_message): ?>
            <div class="alert alert-<?= $flash_message['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_message['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total_conversations'] ?? 0) ?></div>
                    <p class="stat-label">Conversaciones Hoy</p>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +<?= number_format(($stats['total_conversations'] ?? 0) * 0.15) ?> vs ayer
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['active_conversations'] ?? 0) ?></div>
                    <p class="stat-label">Conversaciones Activas</p>
                    <div class="stat-change">
                        <i class="fas fa-circle text-success"></i> En tiempo real
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['pending_conversations'] ?? 0) ?></div>
                    <p class="stat-label">Esperando Agente</p>
                    <?php if (($stats['pending_conversations'] ?? 0) > 0): ?>
                    <div class="stat-change negative">
                        <i class="fas fa-exclamation-triangle"></i> Requiere atención
                    </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['avg_first_response_time'] ?? 0) ?>s</div>
                    <p class="stat-label">Tiempo Promedio Respuesta</p>
                    <div class="stat-change <?= ($stats['avg_first_response_time'] ?? 0) < 300 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-clock"></i> 
                        <?= ($stats['avg_first_response_time'] ?? 0) < 300 ? 'Excelente' : 'Mejorar' ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['online_agents'] ?? 0) ?></div>
                    <p class="stat-label">Agentes En Línea</p>
                    <div class="stat-change">
                        <i class="fas fa-users"></i> de <?= count($active_agents) ?> total
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['messages_today'] ?? 0) ?></div>
                    <p class="stat-label">Mensajes Hoy</p>
                    <div class="stat-change positive">
                        <i class="fas fa-comment"></i> Muy activo
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Panel de conversaciones pendientes -->
                <div class="col-lg-8">
                    <div class="conversations-panel">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <i class="fas fa-clock me-2"></i>
                                Conversaciones Pendientes
                                <?php if (count($pending_conversations) > 0): ?>
                                <span class="badge bg-danger ms-2"><?= count($pending_conversations) ?></span>
                                <?php endif; ?>
                            </h3>
                            <div class="ms-auto">
                                <button class="btn btn-sm btn-light" onclick="autoAssignAll()">
                                    <i class="fas fa-magic me-1"></i> Auto-asignar Todo
                                </button>
                            </div>
                        </div>

                        <div class="conversations-list" id="pendingConversations">
                            <?php if (empty($pending_conversations)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <h5>¡Excelente trabajo!</h5>
                                <p>No hay conversaciones pendientes en este momento.</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($pending_conversations as $conversation): ?>
                            <div class="conversation-item" data-conversation-id="<?= $conversation['id'] ?>" onclick="openConversation(<?= $conversation['id'] ?>)">
                                <div class="conversation-header">
                                    <div class="customer-info">
                                        <h6 class="customer-name">
                                            <?= htmlspecialchars($conversation['customer_name'] ?: 'Cliente') ?>
                                        </h6>
                                        <div class="customer-phone">
                                            <i class="fab fa-whatsapp text-success me-1"></i>
                                            <?= htmlspecialchars($conversation['customer_phone']) ?>
                                        </div>
                                    </div>
                                    <div class="conversation-actions">
                                        <span class="priority-badge priority-<?= $conversation['priority'] ?>">
                                            <?= ucfirst($conversation['priority']) ?>
                                        </span>
                                        <span class="status-badge status-<?= $conversation['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $conversation['status'])) ?>
                                        </span>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="unread-count"><?= $conversation['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="conversation-meta">
                                    <span>
                                        <i class="fas fa-tag me-1"></i>
                                        <?= ucfirst($conversation['category']) ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-globe me-1"></i>
                                        <?= ucfirst($conversation['channel']) ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock me-1"></i>
                                        <?= Helpers::timeAgo($conversation['started_at']) ?>
                                    </span>
                                </div>

                                <?php if (!empty($conversation['last_message'])): ?>
                                <p class="last-message">
                                    <strong>Último mensaje:</strong> 
                                    <?= htmlspecialchars(substr($conversation['last_message'], 0, 100)) ?>...
                                </p>
                                <?php endif; ?>

                                <div class="quick-actions" onclick="event.stopPropagation()">
                                    <select class="form-select form-select-sm agent-select" onchange="assignAgent(<?= $conversation['id'] ?>, this.value)">
                                        <option value="">Asignar agente...</option>
                                        <?php foreach ($active_agents as $agent): ?>
                                        <?php if ($agent['status'] === 'online' && $agent['active_chats'] < $agent['max_concurrent_chats']): ?>
                                        <option value="<?= $agent['id'] ?>">
                                            <?= htmlspecialchars($agent['agent_name']) ?> 
                                            (<?= $agent['active_chats'] ?>/<?= $agent['max_concurrent_chats'] ?>)
                                        </option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="action-btn primary" onclick="openConversation(<?= $conversation['id'] ?>)">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Panel de agentes -->
                <div class="col-lg-4">
                    <div class="agents-panel">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <i class="fas fa-users me-2"></i>
                                Agentes Activos
                            </h3>
                        </div>

                        <div class="agents-list" id="activeAgents">
                            <?php if (empty($active_agents)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <p>No hay agentes activos</p>
                                <a href="<?= Config::getBaseUrl() ?>?route=admin/chat/agents" class="btn btn-primary btn-sm">
                                    Gestionar Agentes
                                </a>
                            </div>
                            <?php else: ?>
                            <?php foreach ($active_agents as $agent): ?>
                            <div class="agent-item" data-agent-id="<?= $agent['id'] ?>">
                                <div class="agent-avatar">
                                    <?php if ($agent['avatar_url']): ?>
                                    <img src="<?= htmlspecialchars($agent['avatar_url']) ?>" alt="<?= htmlspecialchars($agent['agent_name']) ?>">
                                    <?php else: ?>
                                    <?= strtoupper(substr($agent['agent_name'], 0, 2)) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="agent-info">
                                    <h6 class="agent-name"><?= htmlspecialchars($agent['agent_name']) ?></h6>
                                    <div class="agent-status">
                                        <span class="status-dot <?= $agent['status'] ?>"></span>
                                        <?= ucfirst($agent['status']) ?>
                                        <span class="specialization-tag">
                                            <?= ucfirst($agent['specialization']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="agent-workload">
                                    <div class="chat-count">
                                        <?= $agent['active_chats'] ?>/<?= $agent['max_concurrent_chats'] ?>
                                    </div>
                                    <small class="text-muted">
                                        Nivel <?= $agent['priority_level'] ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Métricas rápidas -->
                    <div class="mt-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Métricas de Hoy
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 text-center">
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <div class="h6 text-success mb-0"><?= $stats['active_conversations'] ?? 0 ?></div>
                                            <small class="text-muted">Activas</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <div class="h6 text-warning mb-0"><?= $stats['waiting_agent'] ?? 0 ?></div>
                                            <small class="text-muted">Esperando</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <div class="h6 text-info mb-0"><?= number_format(($stats['messages_today'] ?? 0) / max(($stats['total_conversations'] ?? 1), 1), 1) ?></div>
                                            <small class="text-muted">Msgs/Conv</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <div class="h6 text-primary mb-0"><?= number_format(($stats['avg_first_response_time'] ?? 0) / 60, 1) ?>m</div>
                                            <small class="text-muted">T. Respuesta</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver conversación -->
    <div class="modal fade" id="conversationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-comment me-2"></i>
                        Conversación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="conversationContent">
                    <!-- Contenido dinámico -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
    class ChatDashboard {
        constructor() {
            this.refreshInterval = null;
            this.lastUpdate = Date.now();
            this.init();
        }

        init() {
            this.startRealTimeUpdates();
            this.setupEventListeners();
        }

        startRealTimeUpdates() {
            // Actualizar cada 5 segundos
            this.refreshInterval = setInterval(() => {
                this.fetchUpdates();
            }, 5000);
        }

        setupEventListeners() {
            // Eventos del dashboard
            $(document).ready(() => {
                this.initializeComponents();
            });
        }

        fetchUpdates() {
            $.get('?route=admin/chat/dashboard-updates')
                .done((response) => {
                    if (response.success) {
                        this.updateStats(response.stats);
                        this.updatePendingConversations(response.pending_conversations);
                        this.updateActiveAgents(response.active_agents);
                        this.showNewNotifications(response.notifications);
                    }
                })
                .fail(() => {
                    console.log('Error fetching updates');
                });
        }

        updateStats(stats) {
            // Actualizar valores estadísticos
            Object.keys(stats).forEach(key => {
                const element = $(`[data-stat="${key}"]`);
                if (element.length && element.text() !== stats[key].toString()) {
                    element.text(stats[key]);
                    element.parent().addClass('stat-updated');
                    setTimeout(() => element.parent().removeClass('stat-updated'), 1000);
                }
            });
        }

        updatePendingConversations(conversations) {
            // Actualizar lista de conversaciones pendientes
            const container = $('#pendingConversations');
            const currentIds = new Set();
            
            container.find('.conversation-item').each(function() {
                currentIds.add($(this).data('conversation-id'));
            });

            conversations.forEach(conv => {
                if (!currentIds.has(conv.id)) {
                    this.addConversationToList(conv);
                    this.showNotification('Nueva conversación de ' + (conv.customer_name || 'Cliente'), 'info');
                }
            });
        }

        updateActiveAgents(agents) {
            // Actualizar estado de agentes
            agents.forEach(agent => {
                const agentItem = $(`[data-agent-id="${agent.id}"]`);
                if (agentItem.length) {
                    agentItem.find('.status-dot').removeClass('online busy away offline').addClass(agent.status);
                    agentItem.find('.agent-status').first().text(agent.status.charAt(0).toUpperCase() + agent.status.slice(1));
                    agentItem.find('.chat-count').text(`${agent.active_chats}/${agent.max_concurrent_chats}`);
                }
            });
        }

        addConversationToList(conversation) {
            const conversationHtml = this.buildConversationHTML(conversation);
            $('#pendingConversations .empty-state').remove();
            $('#pendingConversations').prepend(conversationHtml);
        }

        buildConversationHTML(conv) {
            return `
                <div class="conversation-item animate-slide-in" data-conversation-id="${conv.id}" onclick="openConversation(${conv.id})">
                    <div class="conversation-header">
                        <div class="customer-info">
                            <h6 class="customer-name">${conv.customer_name || 'Cliente'}</h6>
                            <div class="customer-phone">
                                <i class="fab fa-whatsapp text-success me-1"></i>
                                ${conv.customer_phone}
                            </div>
                        </div>
                        <div class="conversation-actions">
                            <span class="priority-badge priority-${conv.priority}">${conv.priority.charAt(0).toUpperCase() + conv.priority.slice(1)}</span>
                            <span class="status-badge status-${conv.status}">${conv.status.charAt(0).toUpperCase() + conv.status.slice(1).replace('_', ' ')}</span>
                            ${conv.unread_count > 0 ? `<span class="unread-count">${conv.unread_count}</span>` : ''}
                        </div>
                    </div>
                    <div class="conversation-meta">
                        <span><i class="fas fa-tag me-1"></i>${conv.category.charAt(0).toUpperCase() + conv.category.slice(1)}</span>
                        <span><i class="fas fa-globe me-1"></i>${conv.channel.charAt(0).toUpperCase() + conv.channel.slice(1)}</span>
                        <span><i class="fas fa-clock me-1"></i>Recién iniciada</span>
                    </div>
                    <div class="quick-actions" onclick="event.stopPropagation()">
                        <select class="form-select form-select-sm agent-select" onchange="assignAgent(${conv.id}, this.value)">
                            <option value="">Asignar agente...</option>
                        </select>
                        <button class="action-btn primary" onclick="openConversation(${conv.id})">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    </div>
                </div>
            `;
        }

        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="notification-toast">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-${type === 'info' ? 'info-circle text-primary' : 'exclamation-triangle text-warning'}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="d-block">${message}</strong>
                            <small class="text-muted">${new Date().toLocaleTimeString()}</small>
                        </div>
                        <button class="btn-close btn-sm" onclick="$(this).closest('.notification-toast').remove()"></button>
                    </div>
                </div>
            `);

            $('body').append(notification);
            setTimeout(() => notification.addClass('show'), 100);
            setTimeout(() => notification.remove(), 5000);
        }

        initializeComponents() {
            // Inicializar componentes específicos
            $('.conversation-item').each(function() {
                $(this).on('mouseenter', function() {
                    $(this).find('.quick-actions').fadeIn(200);
                }).on('mouseleave', function() {
                    $(this).find('.quick-actions').fadeOut(200);
                });
            });
        }
    }

    // Funciones globales
    function openConversation(conversationId) {
        // Cargar conversación en modal o nueva ventana
        $.get(`?route=admin/chat/conversation/${conversationId}`)
            .done((response) => {
                if (response.success) {
                    $('#conversationContent').html(response.html);
                    $('#conversationModal').modal('show');
                }
            });
    }

    function assignAgent(conversationId, agentId) {
        if (!agentId) return;

        $.post('?route=admin/chat/assign-conversation', {
            conversation_id: conversationId,
            agent_id: agentId,
            csrf_token: '<?= $csrf_token ?>'
        })
        .done((response) => {
            if (response.success) {
                // Remover conversación de la lista de pendientes
                $(`[data-conversation-id="${conversationId}"]`).fadeOut(500, function() {
                    $(this).remove();
                    if ($('#pendingConversations .conversation-item').length === 0) {
                        $('#pendingConversations').html(`
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <h5>¡Excelente trabajo!</h5>
                                <p>No hay conversaciones pendientes en este momento.</p>
                            </div>
                        `);
                    }
                });
                
                chatDashboard.showNotification('Conversación asignada exitosamente', 'info');
            } else {
                alert('Error: ' + response.error);
            }
        });
    }

    function autoAssignAll() {
        if (!confirm('¿Deseas asignar automáticamente todas las conversaciones pendientes?')) return;

        $.post('?route=admin/chat/auto-assign-all', {
            csrf_token: '<?= $csrf_token ?>'
        })
        .done((response) => {
            if (response.success) {
                chatDashboard.showNotification(`${response.assigned} conversaciones asignadas automáticamente`, 'info');
                refreshDashboard();
            } else {
                alert('Error: ' + response.error);
            }
        });
    }

    function refreshDashboard() {
        location.reload();
    }

    // Inicializar dashboard
    let chatDashboard;
    $(document).ready(() => {
        chatDashboard = new ChatDashboard();
    });
    </script>

    <style>
    .stat-updated {
        background: linear-gradient(135deg, var(--chat-primary), var(--chat-secondary));
        color: white !important;
        transform: scale(1.05);
        transition: all 0.5s ease;
    }

    .animate-slide-in {
        animation: slideInFromTop 0.5s ease;
    }

    @keyframes slideInFromTop {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</body>
</html>
