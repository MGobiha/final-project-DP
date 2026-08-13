<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Automotive News</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/responsive.css" />
  </head>
  <body data-page="news">
    <div class="app-shell">
      <aside class="sidebar">
        <div class="brand">
          <div class="brand-badge">A</div>
          <span>AutoTrack</span>
        </div>
        <nav class="nav">
          <a data-page="dashboard" href="dashboard.html"
            >🏠 <span>Dashboard</span></a
          ><a data-page="vehicles" href="vehicles.html"
            >🚙 <span>Vehicles</span></a
          ><a data-page="service" href="service-history.html"
            >🧾 <span>Service History</span></a
          ><a data-page="maintenance" href="maintenance.html"
            >🗓️ <span>Maintenance</span></a
          ><a data-page="reminders" href="reminders.html"
            >🔔 <span>Reminders</span></a
          ><a data-page="chatbot" href="chatbot.html"
            >🤖 <span>AI Assistant</span></a
          ><a data-page="garages" href="garages.html">📍 <span>Garages</span></a
          ><a data-page="news" href="news.html">📰 <span>News</span></a
          ><a data-page="profile" href="profile.html"
            >👤 <span>Profile</span></a
          >
        </nav>
      </aside>
      <main class="main">
        <header class="topbar">
          <div class="title">
            <h1>Automotive News</h1>
            <p>Automobile Service and Maintenance Tracker</p>
          </div>
          <div class="user-chip">
            <div class="avatar">AM</div>
            <span data-user-name>Alex Morgan</span>
          </div>
        </header>
        <div class="section-head">
          <h2>Latest articles</h2>
          <div>
            <button class="btn btn-secondary" data-news-filter="maintenance">
              Maintenance
            </button>
            <button class="btn btn-secondary" data-news-filter="technology">
              Technology
            </button>
          </div>
        </div>
        <section class="grid">
          <article class="card news-card">
            <div class="news-img">🔧</div>
            <div>
              <span class="badge info">Maintenance</span>
              <h3>Five warning signs your brakes need inspection</h3>
              <p class="muted">
                Learn how noise, vibration and longer stopping distance can
                indicate brake wear.
              </p>
              <button class="btn btn-primary">Read Article</button>
            </div>
          </article>
          <article class="card news-card">
            <div class="news-img">🔋</div>
            <div>
              <span class="badge success">Technology</span>
              <h3>How modern battery monitoring systems work</h3>
              <p class="muted">
                A simple guide to battery health, charging and dashboard alerts.
              </p>
              <button class="btn btn-primary">Read Article</button>
            </div>
          </article>
        </section>
        <script src="js/news.js"></script>
        <div class="footer-note">
          AutoTrack UI prototype • Static frontend demo
        </div>
      </main>
    </div>
    <script src="js/app.js"></script>
  </body>
</html>
