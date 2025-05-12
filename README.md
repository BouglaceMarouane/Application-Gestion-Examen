# 🎓 **ExamManager – School Exam Management System**

## ***✨ Project Description :***

**ExamManager** is a comprehensive web-based application designed to streamline the management of students, classes, subjects, examens, grades, and Export student report cards to PDF. This system provides administrators and teachers with tools to manage academic activities efficiently. It features a user-friendly interface, secure data handling, and robust functionality to meet the needs of modern educational institutions.

## ***🔧 Features and Tools Used :***

### **1. User Management**

- **Functionality:** Registration, login, and role-based access control (administrator, teacher, student).
- **Tools Used:**
  - **PHP:** Backend logic for user authentication and role management.
  - **MySQL:** Database for storing user credentials and roles.
  - **Bootstrap:** Responsive forms for user registration and login.

---

### **2. Student Management**

- **Functionality:** Add, edit, and track student information.
- **Tools Used:**
  - **PHP:** Backend logic for CRUD operations on student data.
  - **MySQL:** Database for storing student records.
  - **Bootstrap:** Responsive forms and tables for managing students.

---

### **3. Class Management**

- **Functionality:** Organize students into classes and academic fields.
- **Tools Used:**
  - **PHP:** Logic for managing class data.
  - **MySQL:** Database for storing class and field information.
  - **Bootstrap:** Forms and tables for class management.

---

### **4. Subject Management**

- **Functionality:** Define subjects with coefficients and assign them to specific academic fields.
- **Tools Used:**
  - **PHP:** Logic for managing subject data.
  - **MySQL:** Database for storing subject details.
  - **Bootstrap:** Forms for adding and editing subjects.

---

### **5. Exam Scheduling**

- **Functionality:** Create, plan, and manage various types of exams.
- **Tools Used:**
  - **PHP:** Logic for scheduling and managing exams.
  - **MySQL:** Database for storing exam schedules.
  - **Bootstrap:** Calendar and forms for scheduling exams.

---

### **6. Grade Management**

- **Functionality:** Record, calculate, and view student grades.
- **Tools Used:**
  - **PHP:** Backend logic for managing grades.
  - **MySQL:** Database for storing grades.
  - **Bootstrap:** Tables for displaying grades.

---

### **7. PDF Report Generation**

- **Functionality:** Export student report cards in PDF format.
- **Tools Used:**
  - **TCPDF:** Library for generating PDF reports.
  - **PHP:** Logic for formatting and exporting data to PDF.

---

### **8. Responsive Design**

- **Functionality:** Ensure the application works seamlessly on various devices and screen sizes.
- **Tools Used:**
  - **Bootstrap:** Grid system and utility classes for responsiveness.
  - **CSS:** Custom styles for hover effects and transitions.

---

### **9. Notifications and Alerts**

- **Functionality:** Display notifications for important actions like grade updates, exam schedules, and profile changes.
- **Tools Used:**
  - **JavaScript:** Dynamic alerts for user feedback.
  - **Bootstrap:** Styled alert components.

---

## ***📷 Screenshots :***

### Home Page
<p align="center">
  <img src="https://via.placeholder.com/800x400" alt="Home Page"/>
  <br>
  <em>🏠 Home Page - The landing page of the application, providing navigation options for login, registration, or accessing the dashboard based on user roles.</em>
</p>

### Admin Dashboard
<p align="center">
  <img src="https://via.placeholder.com/800x400" alt="Admin Dashboard"/>
  <br>
  <em>Manage students, exams, and grades from a centralized dashboard.</em>
</p>

### PDF Report Generation
<p align="center">
  <img src="https://via.placeholder.com/800x400" alt="PDF Report"/>
  <br>
  <em>Generate and download detailed student performance reports in PDF format.</em>
</p>

---

## ***🚧 Challenges Faced :***

### **1. Database Design**
- Creating a flexible database schema to handle different types of exams, grading systems, and academic structures was challenging. A relational design was implemented to maintain relationships between students, classes, subjects, and exams while allowing future scalability.

---

### **2. User Authentication**
- Implementing secure authentication with role-based access control required careful planning. A system was created to manage three distinct roles (administrator, teacher, student) with appropriate permissions for each.

---

### **3. PDF Report Generation**
- Producing professional PDF reports with clean layouts, tables, and charts was technically complex. The TCPDF library was used, and custom templates were created to generate well-formatted report cards.

---

### **4. Responsive Design**
- Ensuring the application works well on various devices and screen sizes required applying responsive design principles. Bootstrap 5 and custom CSS styles were used to create a mobile-friendly interface.

---

## **🛠️ Technologies Used :**

![HTML](https://img.shields.io/badge/HTML-5-orange?logo=html5&logoColor=white) 
![CSS](https://img.shields.io/badge/CSS-3-blue?logo=css3&logoColor=white) 
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-red?logo=Bootstrap&logoColor=white) 
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-green?logo=javascript&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.1-blue?logo=php&logoColor=white) 
![MySQL](https://img.shields.io/badge/MySQL-8.0-gold?logo=mysql&logoColor=white) 
![TCPDF](https://img.shields.io/badge/TCPDF-PDF%20Generation-green)

---

## ***⚙️ Installation Steps :***

1. **📥 Clone the repository** to your machine or download the ZIP files:
   ```
   git clone https://github.com/<your-username>/<repo-name>.git
   ```
2. **📂 Navigate to the project folder** and open it in Visual Studio Code:
   ```
   cd <repo-name> && code .
   ```
3. **📦 Configure the Database**:

  - Import the *dbs.sql* file located in the *config* folder into your MySQL server.
  - Update the database connection details in *config/connection.php*.

4. **🌐 Start a local server**:

  - Place the project in the root folder of your local server (e.g., *htdocs* for XAMPP).
  - Access the application via *http://localhost/<project-folder>*.

5. **📦 Install Dependencies**:

  - Ensure you have Composer installed.
  - Run the following command to install the required libraries:
   ```
   composer install
   ```
