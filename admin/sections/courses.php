<?php
// Kurse aus Datenbank holen
$courses = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section">
    <div class="section-header">
        <h3 class="section-title">Alle Kurse</h3>
        <a href="?page=course-create" class="btn">+ Neuer Kurs</a>
    </div>
    
    <?php if (count($courses) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Kurs</th>
                <th>Beschreibung</th>
                <th>Dauer</th>
                <th>Status</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $course): ?>
            <tr>
                <td>
                    <strong style="color: white;"><?php echo htmlspecialchars($course['title']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 60)) . '...'; ?></td>
                <td><?php echo htmlspecialchars($course['duration'] ?? 'N/A'); ?></td>
                <td>
                    <span class="badge badge-admin">Aktiv</span>
                </td>
                <td>
                    <a href="?page=course-edit&id=<?php echo $course['id']; ?>" style="color: #667eea; margin-right: 12px;">Bearbeiten</a>
                    <a href="#" onclick="deleteCourse(<?php echo $course['id']; ?>)" style="color: #ff6b6b;">Löschen</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">📚</div>
        <p>Noch keine Kurse erstellt</p>
        <a href="?page=course-create" class="btn" style="margin-top: 16px;">Ersten Kurs erstellen</a>
    </div>
    <?php endif; ?>
</div>

<style>
    table a {
        text-decoration: none;
        font-weight: 500;
        transition: opacity 0.2s;
    }
    table a:hover {
        opacity: 0.7;
    }
</style>

<script>
function deleteCourse(courseId) {
    if (confirm('Möchten Sie diesen Kurs wirklich löschen?')) {
        fetch('/admin/api/delete-course.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: courseId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Fehler beim Löschen: ' + data.error);
            }
        });
    }
}
</script>