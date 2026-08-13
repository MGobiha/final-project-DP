<!doctype html>
<html>
  <head>
    <title>Vehicle Form</title>
  </head>
  <body>
    <h2>Enter Vehicle Details</h2>
    <form method="POST">
      <label>Vehicle Number:</label><br />
      <input
        type="text"
        name="vehicle_number"
        value="{{ data.vehicle_number if data else '' }}"
        required
      /><br /><br />

      <label>Model:</label><br />
      <input
        type="text"
        name="model"
        value="{{ data.model if data else '' }}"
        required
      /><br /><br />

      <label>Service Date:</label><br />
      <input
        type="date"
        name="service_date"
        value="{{ data.service_date if data else '' }}"
        required
      /><br /><br />

      <input type="submit" value="Save" />
    </form>
  </body>
</html>
