<?php
/**
 * Детали мероприятия – загружаются по ID из GET-параметра.
 */
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$eventId) {
    echo '<div class="alert alert-danger">Не указан ID мероприятия.</div>';
    exit;
}
?>
<div class="container-fluid py-4">
    <div id="eventDetail">
        <!-- Загружается через JS -->
    </div>
</div>

<script>
    window.eventId = <?= $eventId ?>;
</script>
<script src="/assets/js/parent-event.js"></script>