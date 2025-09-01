<?php require_once __DIR__ . '/_header.php'; ?>
<div class="card">
  <h1>Welcome to Clinique Cover</h1>
  <p class="small">Demo project DAW: Roles: Admin, Doctor, Patient.</p>
  <?php if (!$current_user): ?>
    <p><a class="btn btn-primary" href="register.php">Create an account</a>
      <a class="btn" href="login.php">Log in</a>
    </p>
  <?php else: ?>
    <?php if ($current_user['role'] === 'patient'): ?>
      <p>View your <a href="appointments.php">appointments</a> or browse health info:</p>
      <ul>
        <li><a target="_blank" href="https://www.who.int/">WHO — Health Topics</a></li>
        <li><a target="_blank" href="https://www.nhs.uk/conditions/">NHS — Conditions A-Z</a></li>
      </ul>
    <?php elseif ($current_user['role'] === 'doctor'): ?>
      <p>Manage your <a href="appointments.php">appointments</a> or search a <a href="doctor_patients.php">patient's
          history</a>.</p>
    <?php else: ?>
      <p>Admin tools: <a href="admin_users.php">Users</a> • <a href="appointments.php">Appointments</a></p>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>