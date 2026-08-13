<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Profile</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/responsive.css" />
  </head>

  <body data-page="profile">
    <div class="app-shell">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="brand">
          <div class="brand-badge">A</div>
          <span>AutoTrack</span>
        </div>

        <nav class="nav">
          <a data-page="dashboard" href="dashboard.html">
            🏠 <span>Dashboard</span>
          </a>

          <a data-page="vehicles" href="vehicles.html">
            🚙 <span>Vehicles</span>
          </a>

          <a data-page="service" href="service-history.html">
            🧾 <span>Service History</span>
          </a>

          <a data-page="maintenance" href="maintenance.html">
            🗓️ <span>Maintenance</span>
          </a>

          <a data-page="reminders" href="reminders.html">
            🔔 <span>Reminders</span>
          </a>

          <a data-page="chatbot" href="chatbot.html">
            🤖 <span>AI Assistant</span>
          </a>

          <a data-page="garages" href="garages.html">
            📍 <span>Garages</span>
          </a>

          <a data-page="news" href="news.html"> 📰 <span>News</span> </a>

          <a data-page="profile" href="profile.html">
            👤 <span>Profile</span>
          </a>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="main">
        <!-- Top Bar -->
        <header class="topbar">
          <div class="title">
            <h1>Profile</h1>
            <p>Automobile Service and Maintenance Tracker</p>
          </div>

          <div class="user-chip">
            <div class="avatar">AM</div>
            <span data-user-name>Alex Morgan</span>
          </div>
        </header>

        <!-- Profile Section -->
        <section class="grid grid-2">
          <form
            class="card form-grid"
            onsubmit="
              event.preventDefault();
              alert('Profile updated.');
            "
          >
            <div class="field full">
              <label>Full Name</label>
              <input type="text" value="Alex Morgan" />
            </div>

            <div class="field">
              <label>Email</label>
              <input type="email" value="alex@example.com" />
            </div>

            <div class="field">
              <label>Phone</label>
              <input type="text" value="+94 77 123 4567" />
            </div>

            <div class="field">
              <label>Preferred Reminder Channel</label>

              <select>
                <option>Email and push notification</option>
                <option>Email only</option>
                <option>Push notification only</option>
              </select>
            </div>

            <div class="field">
              <label>Language</label>

              <select>
                <option>English</option>
                <option>Sinhala</option>
                <option>Tamil</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary full">
              Save Changes
            </button>
          </form>

          <!-- Account Settings -->
          <div class="card">
            <h2>Account Settings</h2>

            <p>
              <button
                type="button"
                class="btn btn-secondary"
                data-toast="Password reset link requested."
              >
                Change Password
              </button>
            </p>

            <p>
              <button
                type="button"
                class="btn btn-secondary"
                data-toast="Your data export is being prepared in demo mode."
              >
                Export My Data
              </button>
            </p>

            <p>
              <button
                type="button"
                class="btn btn-danger"
                data-toast="Account deletion is disabled in this prototype."
              >
                Delete Account
              </button>
            </p>
          </div>
        </section>

        <div class="footer-note">
          AutoTrack UI prototype • Static frontend demo
        </div>
      </main>
    </div>

    <script src="js/app.js"></script>
  </body>
</html>
