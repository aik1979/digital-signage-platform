<?php
$userId = $auth->getUserId();

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
        
        $errors = [];
        if (empty($name)) $errors[] = 'Schedule name';
        if (empty($screenId)) $errors[] = 'Screen';
        if (empty($playlistId)) $errors[] = 'Playlist';
        if (empty($startTime)) $errors[] = 'Start time';
        if (empty($endTime)) $errors[] = 'End time';
        if (empty($daysOfWeek)) $errors[] = 'Days of week';
        
        if (!empty($errors)) {
            setFlashMessage('error', 'Missing required fields: ' . implode(', ', $errors));
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
                    setFlashMessage('error', 'Database error: ' . $e->getMessage());
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

<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Schedules</h1>
            <p class="text-gray-400">Manage when playlists display on screens</p>
        </div>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('createScheduleModal')">
            ➕ Create Schedule
        </button>
    </div>

    <?php if (empty($schedules)): ?>
    <!-- Empty State -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-12 text-center">
        <div class="text-6xl mb-4">📅</div>
        <h2 class="text-2xl font-bold text-white mb-2">No Schedules Yet</h2>
        <p class="text-gray-400 mb-6">Create schedules to automatically display different playlists at specific times.</p>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('createScheduleModal')">
            Create Your First Schedule
        </button>
    </div>
    <?php else: ?>
    <!-- Schedules Table -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-700 bg-gray-900">
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Screen</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Playlist</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Days</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Time</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Date Range</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Status</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                        <td class="py-3 px-4 text-white"><?php echo sanitize($schedule['screen_name']); ?></td>
                        <td class="py-3 px-4 text-white"><?php echo sanitize($schedule['playlist_name']); ?></td>
                        <td class="py-3 px-4 text-gray-400 text-sm">
                            <?php
                            $days = json_decode($schedule['days_of_week'], true);
                            $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            $activeDays = [];
                            foreach ($days as $day => $active) {
                                if ($active) $activeDays[] = $dayNames[$day];
                            }
                            echo implode(', ', $activeDays);
                            ?>
                        </td>
                        <td class="py-3 px-4 text-gray-400 text-sm">
                            <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                            <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                        </td>
                        <td class="py-3 px-4 text-gray-400 text-sm">
                            <?php if ($schedule['start_date'] && $schedule['end_date']): ?>
                                <?php echo date('M j', strtotime($schedule['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($schedule['end_date'])); ?>
                            <?php else: ?>
                                Always
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($schedule['is_active']): ?>
                                <span class="inline-block bg-green-600 text-white text-xs px-2 py-1 rounded">Active</span>
                            <?php else: ?>
                                <span class="inline-block bg-gray-600 text-white text-xs px-2 py-1 rounded">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="?page=schedules&edit=<?php echo $schedule['id']; ?>" class="bg-dsp-blue text-white font-semibold py-1 px-3 text-xs rounded-md hover:bg-blue-600 transition">
                                    ✏️ Edit
                                </a>
                                <button type="button" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-1 px-3 text-xs rounded-md hover:from-red-600 hover:to-red-700 transition" onclick="confirmDelete(<?php echo $schedule['id']; ?>)">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Create Schedule Modal -->
<div id="createScheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Create New Schedule</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="toggleModal('createScheduleModal')">&times;</button>
        </div>
        <form method="POST" action="?page=schedules">
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="create_schedule">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="screen_id" class="block text-sm font-medium text-gray-300 mb-2">Screen *</label>
                        <select id="screen_id" name="screen_id" required class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <option value="">Select a screen...</option>
                            <?php foreach ($screens as $screen): ?>
                                <option value="<?php echo $screen['id']; ?>"><?php echo htmlspecialchars($screen['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="playlist_id" class="block text-sm font-medium text-gray-300 mb-2">Playlist *</label>
                        <select id="playlist_id" name="playlist_id" required class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <option value="">Select a playlist...</option>
                            <?php foreach ($playlists as $playlist): ?>
                                <option value="<?php echo $playlist['id']; ?>"><?php echo htmlspecialchars($playlist['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Schedule Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Weekday Breakfast"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Days of Week *</label>
                    <div class="grid grid-cols-7 gap-2">
                        <?php foreach ($daysOfWeek as $index => $day): ?>
                        <label class="flex items-center justify-center bg-gray-700 border border-gray-600 rounded-lg p-2 cursor-pointer hover:bg-gray-600 transition">
                            <input type="checkbox" name="days_of_week[]" value="<?php echo $index; ?>" class="sr-only peer">
                            <span class="text-sm text-gray-300 peer-checked:text-white peer-checked:font-bold"><?php echo substr($day, 0, 3); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-300 mb-2">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-300 mb-2">End Time *</label>
                        <input type="time" id="end_time" name="end_time" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-300 mb-2">Start Date (Optional)</label>
                        <input type="date" id="start_date" name="start_date" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-300 mb-2">End Date (Optional)</label>
                        <input type="date" id="end_date" name="end_date" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleModal('createScheduleModal')">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Create Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Schedule Modal -->
<?php if ($editSchedule): ?>
<div id="editScheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Edit Schedule</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="window.location.href='?page=schedules'">&times;</button>
        </div>
        <form method="POST" action="?page=schedules">
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_schedule">
                <input type="hidden" name="schedule_id" value="<?php echo $editSchedule['id']; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_screen_id" class="block text-sm font-medium text-gray-300 mb-2">Screen *</label>
                        <select id="edit_screen_id" name="screen_id" required class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <?php foreach ($screens as $screen): ?>
                                <option value="<?php echo $screen['id']; ?>" <?php echo ($screen['id'] == $editSchedule['screen_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($screen['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_playlist_id" class="block text-sm font-medium text-gray-300 mb-2">Playlist *</label>
                        <select id="edit_playlist_id" name="playlist_id" required class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <?php foreach ($playlists as $playlist): ?>
                                <option value="<?php echo $playlist['id']; ?>" <?php echo ($playlist['id'] == $editSchedule['playlist_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($playlist['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="edit_name" class="block text-sm font-medium text-gray-300 mb-2">Schedule Name *</label>
                    <input type="text" id="edit_name" name="name" required 
                           value="<?php echo sanitize($editSchedule['name']); ?>"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Days of Week *</label>
                    <div class="grid grid-cols-7 gap-2">
                        <?php 
                        $selectedDays = explode(',', $editSchedule['days_of_week']);
                        foreach ($daysOfWeek as $index => $day): 
                            $checked = in_array($index, $selectedDays) ? 'checked' : '';
                        ?>
                        <label class="flex items-center justify-center bg-gray-700 border border-gray-600 rounded-lg p-2 cursor-pointer hover:bg-gray-600 transition">
                            <input type="checkbox" name="days_of_week[]" value="<?php echo $index; ?>" <?php echo $checked; ?> class="sr-only peer">
                            <span class="text-sm text-gray-300 peer-checked:text-white peer-checked:font-bold"><?php echo substr($day, 0, 3); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_start_time" class="block text-sm font-medium text-gray-300 mb-2">Start Time *</label>
                        <input type="time" id="edit_start_time" name="start_time" required 
                               value="<?php echo $editSchedule['start_time']; ?>"
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label for="edit_end_time" class="block text-sm font-medium text-gray-300 mb-2">End Time *</label>
                        <input type="time" id="edit_end_time" name="end_time" required 
                               value="<?php echo $editSchedule['end_time']; ?>"
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_start_date" class="block text-sm font-medium text-gray-300 mb-2">Start Date (Optional)</label>
                        <input type="date" id="edit_start_date" name="start_date" 
                               value="<?php echo $editSchedule['start_date'] ?? ''; ?>"
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label for="edit_end_date" class="block text-sm font-medium text-gray-300 mb-2">End Date (Optional)</label>
                        <input type="date" id="edit_end_date" name="end_date" 
                               value="<?php echo $editSchedule['end_date'] ?? ''; ?>"
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" <?php echo $editSchedule['is_active'] ? 'checked' : ''; ?> class="w-4 h-4 text-dsp-blue bg-gray-700 border-gray-600 rounded focus:ring-dsp-blue focus:ring-2">
                        <span class="ml-2 text-sm text-gray-300">Schedule is active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="window.location.href='?page=schedules'">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Update Schedule</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="?page=schedules" style="display: none;">
    <input type="hidden" name="action" value="delete_schedule">
    <input type="hidden" name="schedule_id" id="deleteScheduleId">
</form>

<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'none') {
        modal.style.display = 'flex';
    } else {
        modal.style.display = 'none';
    }
}

function confirmDelete(scheduleId) {
    if (confirm('Are you sure you want to delete this schedule?\\n\\nThis action cannot be undone.')) {
        document.getElementById('deleteScheduleId').value = scheduleId;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        const modals = document.querySelectorAll('.fixed');
        modals.forEach(modal => {
            if (modal.contains(event.target) && event.target === modal) {
                if (window.location.search.includes('edit')) {
                    window.location.href = '?page=schedules';
                } else {
                    modal.style.display = 'none';
                }
            }
        });
    }
}
</script>
