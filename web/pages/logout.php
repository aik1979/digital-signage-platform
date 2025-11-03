<?php
$auth->logout();
setFlashMessage('success', 'You have been logged out successfully.');
redirect('login');
