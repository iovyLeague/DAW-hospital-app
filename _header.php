<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/track.php';

function render_role_badge(string $role): string {
    $map = [
        'admin'     => ['label' => 'Admin',     'class' => 'badge-admin',     'color' => '#d9534f'],
        'doctor'    => ['label' => 'Doctor',    'class' => 'badge-doctor',    'color' => '#0275d8'],
        'patient'   => ['label' => 'Patient',   'class' => 'badge-patient',   'color' => '#5cb85c'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'badge-cancelled', 'color' => '#ec7a47'],
    ];

    $role = strtolower(trim($role));
    $info = $map[$role] ?? ['label' => ucfirst($role ?: 'Unknown'), 'class' => 'badge-generic', 'color' => '#6c757d'];

    $label = function_exists('h') ? h($info['label']) : htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8');

    return '<span class="badge ' . $info['class'] . '" style="--bg:' . $info['color'] . ';">' . $label . '</span>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Mini Hospital</title>
  <link rel="stylesheet" href="assets/styles.css"/>
</head>
<body>
<header>
  <div class="container">
    <nav>
      <a href="index.php">Home</a>
      <a href="appointments.php">Appointments</a>
      <a href="external_health.php">Health news</a>
      <a href="contact.php">Contact</a>
      <?php if ($current_user && $current_user['role']==='admin'): ?>
        <a href="admin_users.php">Admin: Users</a>
        <a href="admin_stats.php">Admin: Stats</a>
        <a href="admin_contact.php">Admin: Contact</a>
      <?php endif; ?>
      <?php if ($current_user && $current_user['role']==='doctor'): ?>
        <a href="doctor_patients.php">Doctor: Patients</a>
      <?php endif; ?>
      <?php if ($current_user): ?>
        <span class="small">
          Logged in as <?= h($current_user['name']) ?> <?= render_role_badge($current_user['role']) ?>
        </span>
        <a href="password.php">Password</a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php if ($f = flash_get()): ?>
  <div class="flash <?= h($f['type']) ?>"><?= h($f['msg']) ?></div>
<?php endif; ?>
