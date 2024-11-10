# Backend - Hospital Management System (HMS)

## Overview

The **Backend** of the Hospital Management System (HMS) is built using the **Laravel** PHP framework. It handles all server-side logic, database management, user authentication, and payment processing via **Stripe**.

## Features

- **User Management**:
  - Register, login, and authenticate users.
  - Handle user roles: Guest, Verified User, Doctor, Manager, and Admin.
  - Find clear information about hospital, its departments, doctors and services.
  - Choose and pay for services using Stripe integration.
  - Notifications for paid timeslots and referral codes.
  
- **Doctor Features**:
  - Manage order history.
  - Create referrals for users.
  - Manage available timeslots and services.

- **Manager Features**:
  - Manage hospital departments, doctors, and content using import from file or manually.
  - Generate reports for analytics on hospital and service performance.
  - Manage order history: resend confirmation letter, cancel orders.
  - Create and manage available timeslots and services.
  
- **Admin Features**:
  - Add and manage hospitals.
  - Control user roles and permissions.
  - Manager's features.
  
- **Payment Integration**:
  - Stripe payment gateway integration for handling payments for medical services.

## Technologies Used

- **PHP** (version 8.0 or higher)
- **Laravel** (PHP framework)
- **Stripe** (for payments)
- **MySQL** (or compatible database system)

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
