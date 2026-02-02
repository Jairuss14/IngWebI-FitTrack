const express = require("express");
const mysql = require("mysql2/promise");

const app = express();
app.use(express.json());

// conexión MySQL (misma BD que PHP)
const db = mysql.createPool({
  host: "localhost",
  user: "root",
  password: "", // la tuya
  database: "fittrack_db"
});

// endpoint básico
app.get("/stats/today/:userId", async (req, res) => {
  try {
    const userId = Number(req.params.userId);
    const today = new Date().toISOString().slice(0, 10);

    const [rows] = await db.query(
      `SELECT 
        COUNT(*) AS activities,
        COALESCE(SUM(minutes),0) AS minutes
       FROM activities
       WHERE user_id = ? AND activity_date = ?`,
      [userId, today]
    );

    res.json({
      ok: true,
      date: today,
      activities: rows[0].activities,
      minutes: rows[0].minutes
    });
  } catch (e) {
    res.status(500).json({ ok: false, error: "Server error" });
  }
});

// arrancar servidor
const PORT = 3000;
app.listen(PORT, () => {
  console.log(`Node server running on http://localhost:${PORT}`);
});