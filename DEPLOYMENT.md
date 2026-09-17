# 🚀 Cloud Deployment Guide — Apex Global HRMS

This guide walks you through deploying **Apex Global HRMS** to cloud platforms (**Render**, **Railway**, or **Fly.io**).

> **Why not Netlify?**
> Netlify is a static website host (HTML, CSS, JS) and serverless functions provider. It does not run PHP runtimes or host relational databases like MySQL. The platforms below provide native Docker / PHP execution and host your entire application (frontend + API + database) together.

---

## 📋 Pre-Requisite: Push Your Code to GitHub

If you haven't pushed this repository to GitHub yet:

1. Create a new repository on [GitHub](https://github.com/new) (e.g., `apex-global-hrms`).
2. Open your terminal in this project folder and run:
```bash
git init
git add .
git commit -m "feat: initial commit with production docker configuration"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPOSITORY.git
git push -u origin main
```

---

## 🌟 Method 1: Render.com (Easiest & Free)

Render allows you to deploy containerized web services directly from GitHub on a free tier.

### Step-by-Step Instructions:
1. Go to [Render.com](https://render.com) and sign in with your GitHub account.
2. On your Render dashboard, click the **"New +"** button at the top right and select **"Web Service"**.
3. Choose **"Build and deploy from a Git repository"** and connect your GitHub repository (`apex-global-hrms`).
4. Configure the service settings:
   - **Name:** `apex-global-hrms` (or any name you prefer)
   - **Region:** Choose the region closest to you (e.g., *Oregon (US West)* or *Frankfurt (EU)*)
   - **Branch:** `main`
   - **Runtime:** **Docker** (Render will automatically detect your `Dockerfile`)
   - **Instance Type:** **Free**
5. *(Optional)* Add Environment Variables under **"Advanced"**:
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `DB_CONNECTION`: `sqlite` *(Works out of the box with zero external database setup!)*
6. Click **"Deploy Web Service"**.
7. Render will build the Docker container and deploy it. In 2–3 minutes, you will receive your live URL:
   `https://apex-global-hrms.onrender.com`

---

## 🚂 Method 2: Railway.app (With Cloud MySQL)

Railway makes it effortless to deploy a Docker web service alongside a dedicated cloud MySQL database.

### Step-by-Step Instructions:
1. Go to [Railway.app](https://railway.app) and sign in with GitHub.
2. Click **"New Project"** → **"Deploy from GitHub repo"** and choose your repository.
3. Once the repository is added, click **"+ New"** in the project canvas and select **"Database"** → **"Add MySQL"**.
4. Railway will spin up a cloud MySQL instance and link it.
5. In your web service card:
   - Click on your app card → go to **"Settings"** → scroll down to **"Networking"** → click **"Generate Domain"**.
   - Under **"Variables"**, verify that Railway has automatically shared the MySQL credentials (`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE` or `MYSQL_URL`). Our application code automatically detects these!
6. Railway will redeploy automatically, run the MySQL schema and seed data, and your application will be live at the generated Railway domain.

---

## 🪂 Method 3: Fly.io (Command-Line Deployment)

If you prefer deploying via terminal:

1. Install the Fly CLI:
   - **Windows (PowerShell):** `iwr https://fly.io/install.ps1 -useb | iex`
   - **Mac/Linux:** `curl -L https://fly.io/install.sh | sh`
2. Authenticate:
   ```bash
   fly auth login
   ```
3. Initialize the app inside your project root:
   ```bash
   fly launch
   ```
   *(Accept the defaults; Fly will automatically detect the `Dockerfile`)*.
4. Deploy:
   ```bash
   fly deploy
   ```
5. Open your live app in your browser:
   ```bash
   fly open
   ```

---

## 🐳 Method 4: Local Staging with Docker Compose

To test the containerized environment on your local machine before pushing:

```bash
# Start both the web application and MySQL 8 containers:
docker compose up --build -d

# View status:
docker compose ps

# Access your app in the browser:
http://localhost:8000
```

To stop:
```bash
docker compose down
```

---

## 🔑 Demo Login Accounts

Once deployed, all seed accounts are pre-configured with the default password: **`password123`**

| Role | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@company.com` | `password123` | Full system governance, settings, audit logs |
| **HR Admin** | `hr@company.com` | `password123` | Employees, payroll, recruitment, leave approvals |
| **Manager** | `manager@company.com` | `password123` | Team attendance, leave reviews, performance appraisals |
| **Employee** | `employee@company.com` | `password123` | Personal profile, punch clock, leave requests, payslips |

*(You can also use the 1-click Demo Account Switcher buttons on the login screen for instant sign-in).*
