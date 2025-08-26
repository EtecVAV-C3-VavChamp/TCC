<?php
require_once __DIR__ . "/../classes/Usuario.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Responsividade -->
    <title>Vav Champ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="../index.php">🏆 Vav Champ</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <?php if (isset($_SESSION["usuario"])): ?>
          <li class="nav-item me-3 text-muted">
            👤 <?= htmlspecialchars($_SESSION["usuario"]["nome"]) ?>
          </li>
          <?php if ($_SESSION["usuario"]["tipo"] != 0): ?>
            <li class="nav-item me-2">
              <a class="btn btn-warning" href="../admin/painel.php">Painel Admin</a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="btn btn-outline-danger" href="../logout.php">Sair</a>
          </li>
        <?php else: ?>
          <li class="nav-item me-2">
            <a class="btn btn-outline-primary" href="../admin/login.php">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
