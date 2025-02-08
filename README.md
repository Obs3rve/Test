# Number Classification API

## 🚀 Overview
This project is a **Number Classification API** that classifies a given number based on various mathematical properties and provides a fun fact about it using the NumbersAPI.

## 📌 Features
- **Classifies numbers** as prime, perfect, or Armstrong.
- **Determines parity** (odd/even) of a number.
- **Calculates the sum of digits**.
- **Fetches a fun fact** about the number using NumbersAPI.
- **Provides structured JSON responses**.
- **Handles invalid inputs gracefully**.

---

## 📡 API Endpoints
### **1️⃣ Number Classification Endpoint**
#### **GET** `/api/classify-number?number=<number>`

### ✅ **Successful Response (200 OK)**
#### Example Request:
```
GET https://your-app-name.azurewebsites.net/api/classify-number?number=371
```
#### Example Response:
```json
{
    "number": 371,
    "is_prime": false,
    "is_perfect": false,
    "properties": ["armstrong", "odd"],
    "digit_sum": 11,
    "fun_fact": "371 is an Armstrong number because 3^3 + 7^3 + 1^3 = 371"
}
```

### ❌ **Error Response (400 Bad Request)**
#### Example Request:
```
GET https://your-app-name.azurewebsites.net/api/classify-number?number=abc
```
#### Example Response:
```json
{
    "number": "invalid",
    "error": true
}
```

---

## 🛠️ Project Setup & Installation
### **1️⃣ Clone the Repository**
```sh
git clone https://github.com/yourusername/number-classifier-api.git
cd number-classifier-api
```

### **2️⃣ Create a Virtual Environment & Install Dependencies**
```sh
python -m venv venv
source venv/bin/activate  # On Windows, use: venv\Scripts\activate
pip install -r requirements.txt
```

### **3️⃣ Run the API Locally**
```sh
python app.py
```
**Test Locally:**
```
http://127.0.0.1:5000/api/classify-number?number=371
```

---

## 🚀 Deployment (Azure Web App)
### **1️⃣ Set Up Azure Web App**
1. Go to **Azure Portal** → **App Services** → Click **Create**.
2. Select **Python 3.9+** as the runtime.
3. Choose **Free Plan** and deploy.

### **2️⃣ Set the Startup Command**
In **Azure App Service Configuration**, set the **Startup Command**:
```
gunicorn -w 4 -b 0.0.0.0:8000 app:app
```

### **3️⃣ Push to GitHub & Deploy**
```sh
git add .
git commit -m "Deploying API"
git push origin main
```
Azure will automatically deploy the latest version.

---

## 🎯 Achievements & Challenges
### ✅ **Key Achievements**
- Successfully **built a working API** that meets the project requirements.
- Integrated **external API (NumbersAPI)** to fetch fun facts.
- Hosted the API on **Azure App Service**, making it publicly accessible.
- Implemented **error handling** for invalid inputs.
- Created **automated testing** to validate API responses.

### ❗ **Challenges Faced & Solutions**
1. **GitHub Push Issues** → Fixed by using `git pull --rebase` before pushing.
2. **Azure Deployment Errors** → Resolved by setting the correct startup command (`gunicorn`).
3. **Virtual Environment Issues** → Removed `venv` from GitHub repo and ensured dependencies are installed using `requirements.txt`.
4. **CORS Issues** → Implemented CORS headers to allow cross-origin access.

---

## 🔥 Running API Tests
Run the provided **Bash test script** to validate API responses:
```sh
./test_api.sh "https://your-app-name.azurewebsites.net"
```
This script will:
- Test different numbers
- Validate error handling
- Display results in JSON format

---
