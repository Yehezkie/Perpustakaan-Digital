<?php
// File: includes/alert.php
// Sistem alert custom tanpa Bootstrap - versi profesional dan interaktif

// Fungsi untuk menampilkan alert
function display_alert($type, $message)
{
    $icon = '';
    $color = '';
    $title = '';

    switch ($type) {
        case 'success':
            $icon = '✓';
            $color = '#4CAF50';
            $title = 'Berhasil';
            break;
        case 'error':
            $icon = '⚠';
            $color = '#F44336';
            $title = 'Kesalahan';
            break;
        case 'info':
            $icon = 'ℹ';
            $color = '#2196F3';
            $title = 'Informasi';
            break;
        case 'warning':
            $icon = '⚠';
            $color = '#FF9800';
            $title = 'Peringatan';
            break;
        case 'wa_success':
            $icon = '💬';
            $color = '#25D366';
            $title = 'WhatsApp Terkirim';
            break;
        case 'wa_error':
            $icon = '💬';
            $color = '#FF9800';
            $title = 'WhatsApp Gagal';
            break;
        default:
            $icon = 'ℹ';
            $color = '#2196F3';
            $title = 'Informasi';
    }

    echo <<<HTML
    <div class="custom-alert" data-type="{$type}" style="--alert-color: {$color};">
        <div class="alert-icon">{$icon}</div>
        <div class="alert-content">
            <div class="alert-title">{$title}</div>
            <div class="alert-message">{$message}</div>
        </div>
        <button class="alert-close" onclick="closeAlert(this)">×</button>
    </div>
HTML;
}

// Fungsi untuk menampilkan semua alert yang ada di session
function display_all_alerts()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $alerts = [
        'success' => $_SESSION['success'] ?? null,
        'error' => $_SESSION['error'] ?? null,
        'wa_success' => $_SESSION['wa_success'] ?? null,
        'wa_error' => $_SESSION['wa_error'] ?? null,
        'info' => $_SESSION['info'] ?? null
    ];

    foreach ($alerts as $type => $message) {
        if ($message) {
            display_alert($type, htmlspecialchars($message));
            unset($_SESSION[$type]);
        }
    }
}

// Output CSS styling untuk alert system
if (!isset($GLOBALS['alert_system_loaded'])) {
    echo <<<HTML
    <style>
    /* ALERT SYSTEM STYLING - MODERN DESIGN */
    .custom-alert {
        position: relative;
        padding: 16px 20px;
        margin: 15px 0;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
        display: flex;
        align-items: flex-start;
        border-left: 4px solid var(--alert-color);
        transform: translateY(-10px);
        opacity: 0;
        animation: alertSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        transition: all 0.3s ease;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        z-index: 1000;
        max-width: 500px;
        width: 100%;
    }
    
    @keyframes alertSlideIn {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .alert-icon {
        font-size: 22px;
        margin-right: 15px;
        color: var(--alert-color);
        margin-top: 2px;
        flex-shrink: 0;
    }
    
    .alert-content {
        flex: 1;
    }
    
    .alert-title {
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 3px;
        color: #333;
    }
    
    .alert-message {
        font-size: 14px;
        line-height: 1.5;
        color: #555;
    }
    
    .alert-close {
        background: transparent;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #999;
        padding: 0;
        margin-left: 10px;
        transition: all 0.3s ease;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .alert-close:hover {
        background: rgba(0,0,0,0.05);
        color: #333;
    }
    
    /* Position fixed alerts */
    .alert-fixed {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 380px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .alert-fixed {
            width: calc(100% - 40px);
            left: 20px;
            right: 20px;
            top: 10px;
        }
        
        .custom-alert {
            max-width: 100%;
        }
    }
    </style>
    
    <script>
    function closeAlert(button) {
        const alert = button.closest('.custom-alert');
        alert.style.opacity = '0';
        alert.style.transform = 'translateX(100px)';
        alert.style.marginBottom = '0';
        alert.style.padding = '0';
        alert.style.height = '0';
        alert.style.overflow = 'hidden';
        
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
    
    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.custom-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0.8';
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(100px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 1000);
            }, 5000);
        });
    });
    </script>
HTML;

    $GLOBALS['alert_system_loaded'] = true;
}

// Display all alerts
echo '<div class="alert-fixed">';
display_all_alerts();
echo '</div>';
?>