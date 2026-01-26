<?php
// includes/header.php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$user = $_SESSION["user"] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Expense Tracker</title>

  <link rel="stylesheet" href="/css/style.css">

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <div class="app-shell">
    <aside class="sidebar">
      <div class="sidebar-top">
        <div class="sidebar-profile">
          <?php if ($user && !empty($user["avatar"])): ?>
            <img
              src="/uploads/avatars/<?= htmlspecialchars($user["avatar"]) ?>"
              alt="avatar"
              class="avatar"
            >
          <?php else: ?>
            <div class="avatar avatar-placeholder"></div>
          <?php endif; ?>

          <div class="profile-meta">
            <div class="profile-name">
              <?= htmlspecialchars($user["name"] ?? "Guest") ?>
            </div>

            <?php if ($user): ?>
              <a class="link-small" href="/auth/logout.php">Logout</a>
            <?php else: ?>
              <a class="link-small" href="/auth/login.php">Login</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <nav class="sidebar-nav" aria-label="Main navigation">
        <a class="side-link" href="/index.php">Home</a>
        <a class="side-link" href="/expenses/index.php">Expenses</a>
        <a class="side-link" href="/categories/index.php">Categories</a>
        <a class="side-link" href="/reports/summary.php">Reports</a>
        <a class="side-link" href="/budget/index.php">Budget</a>
        <a class="side-link" href="/settings/index.php">Settings</a>
      </nav>

      <div class="sidebar-footer">
        <div class="brand">ExpenseTracker</div>
      </div>
    </aside>

    <div class="content">
      <main class="container page-enter">