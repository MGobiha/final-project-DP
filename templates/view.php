<!doctype html>
<html>
  <head>
    <title>View Vehicle Data</title>
  </head>
  <body>
    <h2>Vehicle Details</h2>
    {% if data %}
    <p><strong>Vehicle Number:</strong> {{ data.vehicle_number }}</p>
    <p><strong>Model:</strong> {{ data.model }}</p>
    <p><strong>Service Date:</strong> {{ data.service_date }}</p>
    <a href="/form">Edit</a>
    {% else %}
    <p>No data available.</p>
    <a href="/form">Add Vehicle Data</a>
    {% endif %}
  </body>
</html>
