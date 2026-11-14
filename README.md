# Facility Booking System (CodeIgniter 4.7 + MySQL)

Modules: **Calendar view**, **Booking request**, **Notification** (in-app + email).
Roles: **Admin**, **Staff** and **Customer**.

| Role | Can do |
|---|---|
| Customer | Request and cancel their own bookings |
| Staff | Approve / reject / cancel bookings, see calendar and facilities, get notified of new requests |
| Admin | Everything staff can do + add/edit facilities and prices + add/edit/deactivate users (Users page) |
Facilities are sports courts: basketball, volleyball, pickleball, badminton, tennis, futsal, multipurpose.

## Requirements
- PHP 8.2+ with `intl`, `mbstring`, `mysqli`, `curl` enabled (XAMPP/Laragon have them)
- MySQL / MariaDB
- Composer is NOT needed: the CodeIgniter `system/` folder is already included.

## Setup (5 steps)
1. Create the database (phpMyAdmin or MySQL):
   `CREATE DATABASE facility_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Open `.env` and set `database.default.username` / `password` if yours differ.
3. In this folder, run the migrations and sample data:
   ```
   php spark migrate
   php spark db:seed InitialSeeder
   ```
4. Start the server:
   ```
   php spark serve
   ```
5. Open http://localhost:8080

## Default accounts (change after first login)
| Role     | Email                    | Password    |
|----------|--------------------------|-------------|
| Admin    | admin@facility.test      | admin123    |
| Staff    | staff@facility.test      | staff123    |
| Customer | customer@facility.test   | customer123 |

New people can register themselves at `/register` (always as customers). Staff accounts are created by the admin on the **Users** page, where accounts can also be deactivated.

## Email notifications
In-app notifications always work. To also send emails, edit `.env`:
`email.enabled = true` and fill in `email.SMTPUser`, `email.SMTPPass` (Gmail needs an App Password).
Email failures are logged in `writable/logs` and never block a booking.

## How booking works
1. Customer picks a facility + time on the form (or clicks a day on the calendar) -> status **pending**; admins are notified.
2. Admin approves or rejects (with optional remarks); customer is notified.
3. Approved bookings block that time slot; overlapping requests are refused.
4. Customers can cancel their own pending/approved bookings; admins can cancel approved ones.

## Where things are
- `app/Config/Routes.php` – all URLs
- `app/Controllers/` – Auth, Dashboard, Facilities, Calendar, Bookings, Notifications, `Admin/`
- `app/Models/` – User, Facility, Booking, Notification
- `app/Libraries/NotificationService.php` – in-app + email notifications
- `app/Database/Migrations/` and `Seeds/` – tables and sample data
- `app/Views/` – pages (layouts in `app/Views/layouts/`)
- Facility types dropdown: `FacilityModel::TYPES` (icons: `facility_icon()` in `app/Helpers/booking_helper.php`, colors: `.tint-*` in `public/assets/css/app.css`)

## Booking flow for customers
1. **Facilities** > choose an activity (Basketball, Badminton, Pickleball).
2. Choose a court (Court A or B). Each card shows if it is free right now.
3. See the hour-by-hour availability for any day. Booked hours show the **time in / time out**.
   Customers see "Reserved" for other people's sessions; staff and admin see the customer name.
4. Press **Book** on a free hour to open the request form with the time filled in.

Operating hours shown on the availability page are 6 AM to 10 PM (`FacilityModel::OPEN_HOUR` / `CLOSE_HOUR`).

## Court photos
Each court gets a generated illustration automatically (by activity and Court A/B). To use a real photo,
log in as admin > Facilities > Edit, then upload a JPG, PNG or WEBP (up to 3 MB). Photos are kept in `public/uploads/facilities/`.

## Upgrading an existing copy
Copy the new files over your project, then run `php spark migrate`. It keeps all your data and:
adds the staff role column, adds the photo column, renames `Pickleball Court 1/2` to `A/B`, renames `Badminton Court` to `Badminton Court A` and adds `Badminton Court B`.
Other old courts (for example Volleyball) stay; set them to inactive in Admin > Facilities if you do not want them.

## Changing the sample courts
Edit them in the app (log in as admin > Facilities), or change `app/Database/Seeds/InitialSeeder.php` and re-seed.
If you already seeded older data and want the new sample courts, run:
```
php spark migrate:refresh
php spark db:seed InitialSeeder
```
(This wipes ALL data in the tables, including bookings and accounts, then recreates the samples.)

## Going live later
Set `CI_ENVIRONMENT = production` and `app.baseURL` in `.env`, and point the web server at the `public/` folder.
