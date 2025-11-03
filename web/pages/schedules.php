<?php
// Only process if this is being included for display (not POST handling)
if (!isset($userId)) {
    $userId = $auth->getUserId();
}

// Handle schedule actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Create schedule
    if ($_POST['action'] === 'create_schedule') {
        $screenId = intval($_POST['screen_id'] ?? 0);
        $playlistId = intval($_POST['playlist_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $startTime = sanitize($_POST['start_time'] ?? '');
        $endTime = sanitize($_POST['end_time'] ?? '');
        $daysOfWeek = isset($_POST['days_of_week']) ? implode(',', $_POST['days_of_week']) : '';
        $startDate = !empty($_POST['start_date']) ? sanitize($_POST['start_date']) : null;
        $endDate = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
        
        if (empty($name) || empty($startTime) || empty($endTime) || empty($daysOfWeek)) {
            setFlashMessage('error', 'Name, times, and days are required.');
        } else {
            // Verify screen ownership
            $screen = $db->fetchOne("SELECT id FROM screens WHERE id = ? AND user_id = ?", [$screenId, $userId]);
            $playlist = $db->fetchOne("SELECT id FROM playlists WHERE id = ? AND user_id = ?", [$playlistId, $userId]);
            
            if (!$screen || !$playlist) {
                setFlashMessage('error', 'Invalid screen or playlist.');
            } else {
                try {
                    $scheduleId = $db->insert('schedules', [
                        'user_id' => $userId,
                        'screen_id' => $screenId,
                        'playlist_id' => $playlistId,
                        'name' => $name,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'days_of_week' => $daysOfWeek,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'is_active' => 1
                    ]);
                    
                    logActivity($db, $userId, 'schedule_created', 'schedule', $scheduleId, 'Created schedule: ' . $name);
                    setFlashMessage('success', 'Schedule created successfully!');
                    redirect('schedules');
                } catch (Exception $e) {
                    setFlashMessage('error', 'Failed to create schedule.');
                }
            }
        }
    }
    
    // Update schedule
    if ($_POST['action'] === 'update_schedule') {
        $scheduleId = intval($_POST['schedule_id'] ?? 0);
        $screenId = intval($_POST['screen_id'] ?? 0);
        $playlistId = intval($_POST['playlist_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $startTime = sanitize($_POST['start_time'] ?? '');
        $endTime = sanitize($_POST['end_time'] ?? '');
        $daysOfWeek = isset($_POST['days_of_week']) ? implode(',', $_POST['days_of_week']) : '';
        $startDate = !empty($_POST['start_date']) ? sanitize($_POST['start_date']) : null;
        $endDate = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $schedule = $db->fetchOne("SELECT id FROM schedules WHERE id = ? AND user_id = ?", [$scheduleId, $userId]);
        
        if (!$schedule) {
            setFlashMessage('error', 'Schedule not found.');
        } else {
            try {
                $db->update('schedules', [
                    'screen_id' => $screenId,
                    'playlist_id' => $playlistId,
                    'name' => $name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'days_of_week' => $daysOfWeek,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_active' => $isActive
                ], 'id = :id', ['id' => $scheduleId]);
                
                logActivity($db, $userId, 'schedule_updated', 'schedule', $scheduleId, 'Updated schedule: ' . $name);
                setFlashMessage('success', 'Schedule updated successfully!');
                redirect('schedules');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to update schedule.');
            }
        }
    }
    
    // Delete schedule
    if ($_POST['action'] === 'delete_schedule') {
        $scheduleId = intval($_POST['schedule_id'] ?? 0);
        
        $schedule = $db->fetchOne("SELECT name FROM schedules WHERE id = ? AND user_id = ?", [$scheduleId, $userId]);
        
        if (!$schedule) {
            setFlashMessage('error', 'Schedule not found.');
        } else {
            try {
                $db->delete('schedules', 'id = :id', ['id' => $scheduleId]);
                logActivity($db, $userId, 'schedule_deleted', 'schedule', $scheduleId, 'Deleted schedule: ' . $schedule['name']);
                setFlashMessage('success', 'Schedule deleted successfully!');
                redirect('schedules');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to delete schedule.');
            }
        }
    }
}

// Only fetch data if not a POST request (prevents errors on refresh)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Get all schedules with screen and playlist names
    $schedules = $db->fetchAll(
    "SELECT s.*, sc.name as screen_name, p.name as playlist_name
     FROM schedules s
     JOIN screens sc ON s.screen_id = sc.id
     JOIN playlists p ON s.playlist_id = p.id
     WHERE s.user_id = ?
     ORDER BY s.created_at DESC",
    [$userId]
);

    // Get screens and playlists for dropdowns
    $screens = $db->fetchAll("SELECT id, name FROM screens WHERE user_id = ? ORDER BY name", [$userId]);
    $playlists = $db->fetchAll("SELECT id, name FROM playlists WHERE user_id = ? ORDER BY name", [$userId]);

    // Get schedule for editing
    $editSchedule = null;
    if (isset($_GET['edit'])) {
        $scheduleId = intval($_GET['edit']);
        $editSchedule = $db->fetchOne("SELECT * FROM schedules WHERE id = ? AND user_id = ?", [$scheduleId, $userId]);
    }
} else {
    // POST request - set empty arrays to prevent errors
    $schedules = [];
    $screens = [];
    $playlists = [];
    $editSchedule = null;
}

// Days of week
$daysOfWeek = [
    '0' => 'Sunday',
    '1' => 'Monday',
    '2' => 'Tuesday',
    '3' => 'Wednesday',
    '4' => 'Thursday',
    '5' => 'Friday',
    '6' => 'Saturday'
];
?>

<div class="page-header">
    <div class="header-content">
        <div>
            <h1>Schedules</h1>
            <p>Manage time-based playlist switching</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="toggleModal('createScheduleModal')">
            ➕ Create Schedule
        </button>
    </div>
</div>

<?php if (empty($schedules)): ?>
<div class="empty-state">
    <h2>📅 No Schedules Yet</h2>
    <p>Create schedules to automatically switch playlists based on time and day.</p>
    <p class="help-text">Example: Show breakfast menu 6-11am, lunch 11am-3pm, dinner 3pm-close</p>
    <button type="button" class="btn btn-primary" onclick="toggleModal('createScheduleModal')">
        Create Your First Schedule
    </button>
</div>
<?php else: ?>
<div class="schedules-list">
    <?php foreach ($schedules as $schedule): ?>
    <div class="schedule-card <?php echo $schedule['is_active'] ? '' : 'inactive'; ?>">
        <div class="schedule-header">
            <div>
                <h3><?php echo sanitize($schedule['name']); ?></h3>
                <div class="schedule-meta">
                    <span>📺 <?php echo sanitize($schedule['screen_name']); ?></span>
                    <span>📋 <?php echo sanitize($schedule['playlist_name']); ?></span>
                </div>
            </div>
            <?php if (!$schedule['is_active']): ?>
                <span class="badge badge-warning">Inactive</span>
            <?php endif; ?>
        </div>
        
        <div class="schedule-details">
            <div class="detail-row">
                <span class="detail-label">⏰ Time:</span>
                <span class="detail-value"><?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - <?php echo date('g:i A', strtotime($schedule['end_time'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">📅 Days:</span>
                <span class="detail-value">
                    <?php 
                    $days = explode(',', $schedule['days_of_week']);
                    $dayNames = array_map(function($d) use ($daysOfWeek) { return $daysOfWeek[$d]; }, $days);
                    echo implode(', ', $dayNames);
                    ?>
                </span>
            </div>
            
            <?php if ($schedule['start_date'] || $schedule['end_date']): ?>
            <div class="detail-row">
                <span class="detail-label">📆 Date Range:</span>
                <span class="detail-value">
                    <?php 
                    if ($schedule['start_date']) echo date('M j, Y', strtotime($schedule['start_date']));
                    if ($schedule['start_date'] && $schedule['end_date']) echo ' - ';
                    if ($schedule['end_date']) echo date('M j, Y', strtotime($schedule['end_date']));
                    ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="schedule-actions">
            <a href="?page=schedules&edit=<?php echo $schedule['id']; ?>" class="btn btn-secondary btn-sm">
                ✏️ Edit
            </a>
            <button type="button" class="btn btn-danger btn-sm" 
                    onclick="confirmDelete(<?php echo $schedule['id']; ?>, '<?php echo addslashes($schedule['name']); ?>')">
                🗑️ Delete
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Create Schedule Modal -->
<div id="createScheduleModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create New Schedule</h2>
            <button type="button" class="close-btn" onclick="toggleModal('createScheduleModal')">&times;</button>
        </div>
        <form method="POST" action="?page=schedules">
            <input type="hidden" name="action" value="create_schedule">
            
            <div class="form-group">
                <label for="name">Schedule Name *</label>
                <input type="text" id="name" name="name" required 
                       placeholder="e.g., Breakfast Hours, Weekend Special">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="screen_id">Screen *</label>
                    <select id="screen_id" name="screen_id" required>
                        <option value="">-- Select Screen --</option>
                        <?php foreach ($screens as $screen): ?>
                            <option value="<?php echo $screen['id']; ?>"><?php echo sanitize($screen['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="playlist_id">Playlist *</label>
                    <select id="playlist_id" name="playlist_id" required>
                        <option value="">-- Select Playlist --</option>
                        <?php foreach ($playlists as $playlist): ?>
                            <option value="<?php echo $playlist['id']; ?>"><?php echo sanitize($playlist['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time">End Time *</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Days of Week *</label>
                <div class="checkbox-group">
                    <?php foreach ($daysOfWeek as $value => $label): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="days_of_week[]" value="<?php echo $value; ?>">
                            <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date (Optional)</label>
                    <input type="date" id="start_date" name="start_date">
                    <small>Leave blank for no start date limit</small>
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date (Optional)</label>
                    <input type="date" id="end_date" name="end_date">
                    <small>Leave blank for no end date limit</small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('createScheduleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Schedule Modal -->
<?php if ($editSchedule): ?>
<div id="editScheduleModal" class="modal" style="display: block;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Schedule</h2>
            <button type="button" class="close-btn" onclick="window.location.href='?page=schedules'">&times;</button>
        </div>
        <form method="POST" action="?page=schedules">
            <input type="hidden" name="action" value="update_schedule">
            <input type="hidden" name="schedule_id" value="<?php echo $editSchedule['id']; ?>">
            
            <div class="form-group">
                <label for="edit_name">Schedule Name *</label>
                <input type="text" id="edit_name" name="name" required 
                       value="<?php echo sanitize($editSchedule['name']); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_screen_id">Screen *</label>
                    <select id="edit_screen_id" name="screen_id" required>
                        <?php foreach ($screens as $screen): ?>
                            <option value="<?php echo $screen['id']; ?>" 
                                    <?php echo $editSchedule['screen_id'] == $screen['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($screen['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_playlist_id">Playlist *</label>
                    <select id="edit_playlist_id" name="playlist_id" required>
                        <?php foreach ($playlists as $playlist): ?>
                            <option value="<?php echo $playlist['id']; ?>" 
                                    <?php echo $editSchedule['playlist_id'] == $playlist['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($playlist['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_start_time">Start Time *</label>
                    <input type="time" id="edit_start_time" name="start_time" required 
                           value="<?php echo $editSchedule['start_time']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="edit_end_time">End Time *</label>
                    <input type="time" id="edit_end_time" name="end_time" required 
                           value="<?php echo $editSchedule['end_time']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Days of Week *</label>
                <div class="checkbox-group">
                    <?php 
                    $selectedDays = explode(',', $editSchedule['days_of_week']);
                    foreach ($daysOfWeek as $value => $label): 
                    ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="days_of_week[]" value="<?php echo $value; ?>"
                                   <?php echo in_array($value, $selectedDays) ? 'checked' : ''; ?>>
                            <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_start_date">Start Date (Optional)</label>
                    <input type="date" id="edit_start_date" name="start_date" 
                           value="<?php echo $editSchedule['start_date'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="edit_end_date">End Date (Optional)</label>
                    <input type="date" id="edit_end_date" name="end_date" 
                           value="<?php echo $editSchedule['end_date'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" <?php echo $editSchedule['is_active'] ? 'checked' : ''; ?>>
                    Schedule is active
                </label>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=schedules'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Schedule</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Delete Form -->
<form id="deleteForm" method="POST" action="?page=schedules" style="display: none;">
    <input type="hidden" name="action" value="delete_schedule">
    <input type="hidden" name="schedule_id" id="deleteScheduleId">
</form>

<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'none') {
        modal.style.display = 'block';
    } else {
        modal.style.display = 'none';
    }
}

function confirmDelete(scheduleId, scheduleName) {
    if (confirm('Are you sure you want to delete "' + scheduleName + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteScheduleId').value = scheduleId;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        if (window.location.search.includes('edit')) {
            window.location.href = '?page=schedules';
        }
    }
}
</script>
