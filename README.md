# Find Taxi

A JSON-backed PHP taxi dispatch API with a live control-room dashboard. The system models a fleet of taxis and a stream of passengers along a 1D highway (0–1000km), auto-dispatching the nearest available driver, then carries each ride through its full lifecycle — request → assign → pickup → complete — with dynamic pricing, ratings, payments, ride sharing, and analytics along the way.

## ⚙️ About the Project

Everything runs over a REST API backed by plain JSON files — no database required. Drivers, passengers, rides, payments, and ratings are all persisted as JSON under `storage/`, and every read/write goes through a thin `JsonStorage` layer. The bundled **Find Taxi** dashboard (`index.php`) visualizes the fleet on a live "highway radar" and lets you drive a ride through its entire lifecycle from the browser — request it, assign a driver, pick up, complete, and rate — watching the API respond in real time.

## 🛠️ Tech Stack

**Language:** PHP 7.4 / PHP 8.x.
**Paradigm:** Object-Oriented Programming (OOP), Service Layer + Controller pattern.
**Storage:** JSON files (no database, no ORM).
**API:** RESTful, JSON request/response, PSR-4 autoloading.
**Frontend:** Vanilla JavaScript (ES6, no framework) + CSS3 (custom properties, keyframe animations).
**Fonts:** Google Fonts — Big Shoulders Display, JetBrains Mono, Rajdhani.
**Dependencies:** None (Pure Vanilla PHP + vanilla JS/CSS).

## 🚀 Features

- **Ride Management** — Request, accept, pickup, complete, and cancel rides with full lifecycle tracking.
- **Driver Dispatch** — Automatically assigns the nearest available driver based on live position.
- **Dynamic Pricing** — Zone-based base fare + per-km rate, with a surge multiplier (1.0x–2.0x) that scales with active demand.
- **Ride Sharing** — Combines compatible rides on similar routes for a 15% discount to both passengers.
- **Rating System** — 5-star ratings with comments for drivers and passengers after every ride.
- **Payment Processing** — Multiple payment methods with automatic refunds on cancellation.
- **Analytics** — System-wide, per-driver, and per-passenger statistics.
- **Live Dashboard** — Interactive control console with a highway radar, dispatch log, and ride tracker.

## 💻 Example API Response

Requesting a ride (`POST /api/ride/request`) instantly returns the priced, dispatch-ready ride:

```json
{
  "success": true,
  "data": {
    "ride": {
      "id": 1,
      "passenger_id": 1,
      "driver_id": null,
      "status": "pending",
      "pickup_position": 100,
      "dropoff_position": 500,
      "distance": 400,
      "price": 605.0,
      "surge_coefficient": 1.0,
      "zone_id": 1,
      "created_at": "2026-07-13 10:30:00"
    }
  }
}
```
