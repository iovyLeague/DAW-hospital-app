<?php
require_once __DIR__ . '/lib/auth.php';
require_login();

$action = $_GET['action'] ?? 'list';

function can_edit_appointment($ap, $user)
{
  if ($user['role'] === 'admin')
    return true;
  if ($user['role'] === 'doctor' && $ap['doctor_id'] === $user['id'])
    return true;
  // Patients can edit urm appnmts
  if ($user['role'] === 'patient' && $ap['patient_id'] === $user['id']) {
    return strtotime($ap['scheduled_at']) > time();
  }
  return false;
}
function can_cancel_appointment($ap, $user)
{
  $isFuture = strtotime($ap['scheduled_at']) > time();
  if ($user['role'] === 'admin')
    return true;
  if ($user['role'] === 'doctor' && $ap['doctor_id'] === $user['id'])
    return true;
  if ($user['role'] === 'patient' && $ap['patient_id'] === $user['id'])
    return $isFuture;
  return false;
}

if ($action === 'create' || $action === 'edit') {
  if ($action === 'create') {
    if (!in_array($current_user['role'], ['admin', 'doctor', 'patient'])) {
      http_response_code(403);
      exit('Forbidden');
    }
  }

  $id = intval($_GET['id'] ?? 0);
  $ap = ['id' => 0, 'patient_id' => '', 'doctor_id' => '', 'scheduled_at' => '', 'status' => 'scheduled', 'notes' => ''];
  if ($action === 'edit') {
    $s = $pdo->prepare("SELECT * FROM appointments WHERE id=?");
    $s->execute([$id]);
    $ap = $s->fetch();
    if (!$ap) {
      flash_set('Appointment not found', 'error');
      redirect('appointments.php');
    }
    if (!can_edit_appointment($ap, $current_user)) {
      http_response_code(403);
      exit('Forbidden');
    }
  }

  $pat = $pdo->query("SELECT id,name FROM users WHERE role='patient' ORDER BY name")->fetchAll();
  $doc = $pdo->query("SELECT id,name FROM users WHERE role='doctor' ORDER BY name")->fetchAll();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $scheduled_at = $_POST['scheduled_at'] ?? '';
    $status = $_POST['status'] ?? 'scheduled';
    $notes = $_POST['notes'] ?? null;

    if ($current_user['role'] === 'doctor') {
      $doctor_id = $current_user['id'];
    }
    if ($current_user['role'] === 'patient') {
      $patient_id = $current_user['id'];
    }

    if ($action === 'create') {
      $ins = $pdo->prepare("INSERT INTO appointments (patient_id,doctor_id,scheduled_at,status,notes,created_by) VALUES (?,?,?,?,?,?)");
      $ins->execute([$patient_id, $doctor_id, $scheduled_at, $status, $notes, $current_user['id']]);
      flash_set('Appointment created');
    } else {
      $upd = $pdo->prepare("UPDATE appointments SET patient_id=?, doctor_id=?, scheduled_at=?, status=?, notes=?, updated_by=?, updated_at=NOW() WHERE id=?");
      $upd->execute([$patient_id, $doctor_id, $scheduled_at, $status, $notes, $current_user['id'], $ap['id']]);
      flash_set('Appointment updated');
    }
    redirect('appointments.php');
  }

  require_once __DIR__ . '/_header.php';
  ?>
  <div class="card">
    <h2><?= $action === 'create' ? 'Create' : 'Edit' ?> appointment</h2>
    <form method="post">
      <label>Patient</label>
      <select name="patient_id" required <?= $current_user['role'] === 'patient' ? 'disabled' : '' ?>>
        <option value="">Select patient</option>
        <?php foreach ($pat as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $ap['patient_id'] == $p['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($current_user['role'] === 'patient'): ?>
        <input type="hidden" name="patient_id" value="<?= $current_user['id'] ?>">
      <?php endif; ?>

      <label>Doctor</label>
      <select name="doctor_id" required <?= $current_user['role'] === 'doctor' ? 'disabled' : '' ?>>
        <option value="">Select doctor</option>
        <?php foreach ($doc as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $ap['doctor_id'] == $d['id'] ? 'selected' : '' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($current_user['role'] === 'doctor'): ?>
        <input type="hidden" name="doctor_id" value="<?= $current_user['id'] ?>">
      <?php endif; ?>

      <label>Date & time</label>
      <input type="datetime-local" name="scheduled_at"
        value="<?= $ap['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($ap['scheduled_at'])) : '' ?>" required>

      <label>Status</label>
      <select name="status">
        <?php foreach (['scheduled', 'completed', 'cancelled'] as $st): ?>
          <option <?= $ap['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>

      <label>Notes</label>
      <textarea name="notes" rows="3"><?= h($ap['notes']) ?></textarea>

      <button class="btn btn-primary"><?= $action === 'create' ? 'Create' : 'Save' ?></button>
    </form>
  </div>
  <?php require_once __DIR__ . '/_footer.php'; ?>
  <?php
  exit;
}

if ($action === 'cancel') {
  $id = intval($_GET['id'] ?? 0);
  $s = $pdo->prepare("SELECT * FROM appointments WHERE id=?");
  $s->execute([$id]);
  $ap = $s->fetch();
  if (!$ap || !can_cancel_appointment($ap, $current_user)) {
    http_response_code(403);
    exit('Forbidden');
  }
  $pdo->prepare("UPDATE appointments SET status='cancelled', updated_by=?, updated_at=NOW() WHERE id=?")->execute([$current_user['id'], $id]);
  flash_set('Appointment cancelled');
  redirect('appointments.php');
}

$where = "1=1";
$args = [];

if ($current_user['role'] === 'patient') {
  $where = "patient_id = ?";
  $args = [$current_user['id']];
} elseif ($current_user['role'] === 'doctor') {
  $where = "doctor_id = ?";
  $args = [$current_user['id']];
}

$q = $pdo->prepare("SELECT a.*, p.name AS patient_name, d.name AS doctor_name
                    FROM appointments a
                    JOIN users p ON p.id=a.patient_id
                    JOIN users d ON d.id=a.doctor_id
                    WHERE $where
                    ORDER BY a.scheduled_at DESC");
$q->execute($args);
$rows = $q->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Appointments</h2>
  <p class="actions">
    <?php if (in_array($current_user['role'], ['admin', 'doctor', 'patient'])): ?>
      <a class="btn btn-primary" href="appointments.php?action=create">New appointment</a>
    <?php endif; ?>
  </p>
  <table class="table">
    <thead>
      <tr>
        <?php if ($current_user['role'] !== 'patient'): ?>
          <th>Patient</th><?php endif; ?>
        <th>Doctor</th>
        <th>Date</th>
        <th>Status</th>
        <th>Notes</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <?php if ($current_user['role'] !== 'patient'): ?>
            <td><?= h($r['patient_name']) ?></td><?php endif; ?>
          <td><?= h($r['doctor_name']) ?></td>
          <td><?= h($r['scheduled_at']) ?></td>
          <td><?= role_badge($r['status']) ?></td>
          <td><?= h($r['notes']) ?></td>
          <td class="actions">
            <?php if (can_edit_appointment($r, $current_user)): ?>
              <a href="appointments.php?action=edit&id=<?= $r['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if (can_cancel_appointment($r, $current_user)): ?>
              <a href="appointments.php?action=cancel&id=<?= $r['id'] ?>"
                onclick="return confirm('Cancel this appointment?')">Cancel</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="6"><em>No appointments.</em></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>