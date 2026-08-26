<?php
/**
 * Детальная страница мероприятия.
 * ID передаётся через GET-параметр ?id.
 */
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($eventId <= 0) {
    echo '<div class="alert alert-danger">ID мероприятия не указан.</div>';
    return;
}
?>
<div id="student-event" data-event-id="<?= $eventId ?>">
    <div id="event-detail">
        <p class="text-muted">Загрузка...</p>
    </div>
</div>

<script src="/assets/js/student-event.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initPage();
    });
</script>