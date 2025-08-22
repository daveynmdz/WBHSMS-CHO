<?php
// Example: Fetch patient data from a database or API here.
// For demo, we'll use static variables. Replace with your backend logic.
$patient = [
    "full_name" => "John Doe",
    "patient_id" => "123456",
    "age" => 32,
    "sex" => "Male",
    "dob" => "1993-05-19",
    "blood_type" => "O+",
    "civil_status" => "Single",
    "religion" => "Roman Catholic",
    "occupation" => "Software Engineer",
    "contact" => "+63 900 123 4567",
    "email" => "john.doe@email.com",
    "philhealth_id" => "PH123456789",
    "address" => "123 Main St",
    "barangay" => "Barangay Uno",
    "emergency" => [
        "name" => "Jane Doe",
        "relationship" => "Sister",
        "contact" => "+63 900 765 4321"
    ],
    "lifestyle" => [
        "smoking" => "Never",
        "alcohol" => "Occasional",
        "activity" => "Active",
        "diet" => "Balanced"
    ],
    "vitals" => [
        "height" => "172 cm",
        "weight" => "68 kg",
        "bp" => "120/80 mmHg",
        "cardiac_rate" => "72 bpm",
        "temperature" => "36.8 °C",
        "respiratory_rate" => "16/min"
    ],
    // Add arrays for appointments and medical history as needed
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Patient Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Sidebar/Nav/Main styles -->
    <link rel="stylesheet" href="assets/css/patientUI.css">
    <!-- Profile-specific styles -->
    <link rel="stylesheet" href="assets/css/patientProfile.css">
</head>
<body>
    <!-- Mobile top bar -->
    <div class="mobile-topbar">
        <a href="patientUIHomepage.php">
            <img id="topbarLogo" class="logo"
                src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
    </div>
    <button class="mobile-toggle" onclick="toggleNav()" aria-label="Toggle Menu">
        <i id="menuIcon" class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay" onclick="closeNav()"></div>
    <nav class="nav" id="sidebar">
        <a href="patientUIHomepage.php">
            <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527"
                alt="Sidebar Logo" />
        </a>
        <div class="menu">
            <a href="#"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="#"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
            <a href="#"><i class="fas fa-vials"></i> Laboratory</a>
            <a href="#"><i class="fas fa-file-invoice-dollar icon"></i> Billing</a>
        </div>
        <div class="user-profile">
            <a href="patientProfile.php" style="text-decoration: none; color: inherit;">
                <div class="user-info">
                    <img src="https://i.pravatar.cc/100?img=3" alt="User Profile" />
                    <div class="user-text">
                        <strong><?= htmlspecialchars($patient['full_name']) ?></strong>
                        <small>Patient #<?= htmlspecialchars($patient['patient_id']) ?></small>
                    </div>
                    <span class="tooltip">View Profile</span>
                </div>
            </a>
            <div class="user-actions">
                <a href="#"><i class="fas fa-bell"></i> Notifications</a>
                <a href="#"><i class="fas fa-cog"></i> Settings</a>
                <a href="#"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
    </nav>
    <section class="homepage">
        <h1>PATIENT PROFILE</h1>
        <div class="profile-layout">
            <!-- LEFT SIDE -->
            <div class="profile-wrapper">
                <!-- Top Header Card -->
                <div class="profile-header">
                    <img class="profile-photo" src="https://i.ibb.co/Y0m9XGk/user-icon.png" alt="User">
                    <div class="profile-name">
                        <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                        <p>Patient #<?= htmlspecialchars($patient['patient_id']) ?></p>
                    </div>
                </div>
                <!-- Personal Information -->
                <div class="profile-card">
                    <h3>Personal Information</h3>
                    <div class="info-section">
                        <div class="info-row"><span>AGE:</span><span><?= htmlspecialchars($patient['age']) ?></span></div>
                        <div class="info-row"><span>SEX:</span><span><?= htmlspecialchars($patient['sex']) ?></span></div>
                        <div class="info-row"><span>DATE OF BIRTH:</span><span><?= htmlspecialchars($patient['dob']) ?></span></div>
                        <div class="info-row"><span>BLOOD TYPE:</span><span><?= htmlspecialchars($patient['blood_type']) ?></span></div>
                        <div class="info-row"><span>CIVIL STATUS:</span><span><?= htmlspecialchars($patient['civil_status']) ?></span></div>
                        <div class="info-row"><span>RELIGION:</span><span><?= htmlspecialchars($patient['religion']) ?></span></div>
                        <div class="info-row"><span>OCCUPATION:</span><span><?= htmlspecialchars($patient['occupation']) ?></span></div>
                        <div class="info-row"><span>CONTACT NO.:</span><span><?= htmlspecialchars($patient['contact']) ?></span></div>
                        <div class="info-row"><span>EMAIL:</span><span><?= htmlspecialchars($patient['email']) ?></span></div>
                        <div class="info-row"><span>PHILHEALTH ID:</span><span><?= htmlspecialchars($patient['philhealth_id']) ?></span></div>
                        <div class="info-row"><span>HOUSE NO. & STREET:</span><span><?= htmlspecialchars($patient['address']) ?></span></div>
                        <div class="info-row"><span>BARANGAY:</span><span><?= htmlspecialchars($patient['barangay']) ?></span></div>
                    </div>
                </div>
                <!-- Emergency Contact -->
                <div class="profile-card">
                    <h3>Emergency Contact</h3>
                    <div class="info-section">
                        <div class="info-row"><span>NAME:</span><span><?= htmlspecialchars($patient['emergency']['name']) ?></span></div>
                        <div class="info-row"><span>RELATIONSHIP:</span><span><?= htmlspecialchars($patient['emergency']['relationship']) ?></span></div>
                        <div class="info-row"><span>CONTACT NO.:</span><span><?= htmlspecialchars($patient['emergency']['contact']) ?></span></div>
                    </div>
                </div>
                <!-- Lifestyle Information -->
                <div class="profile-card">
                    <h3>Lifestyle Information</h3>
                    <div class="info-section">
                        <div class="info-row"><span>SMOKING STATUS:</span><span><?= htmlspecialchars($patient['lifestyle']['smoking']) ?></span></div>
                        <div class="info-row"><span>ALCOHOL INTAKE:</span><span><?= htmlspecialchars($patient['lifestyle']['alcohol']) ?></span></div>
                        <div class="info-row"><span>PHYSICAL ACTIVITY:</span><span><?= htmlspecialchars($patient['lifestyle']['activity']) ?></span></div>
                        <div class="info-row"><span>DIETARY HABIT:</span><span><?= htmlspecialchars($patient['lifestyle']['diet']) ?></span></div>
                    </div>
                </div>
            </div>
            <!-- RIGHT SIDE -->
            <div class="patient-summary-section">
                <!-- 1. Latest Vitals -->
                <div class="summary-card vitals-section">
                    <div class="section-header">
                        <h2>Latest Vitals</h2>
                        <small><i>as of <?= date("F d, Y") ?></i></small>
                    </div>
                    <div class="vitals-grid">
                        <div class="vital-card">
                            <i class="fas fa-ruler-vertical"></i>
                            <div><span class="label">HEIGHT</span><br><strong><?= htmlspecialchars($patient['vitals']['height']) ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-weight"></i>
                            <div><span class="label">WEIGHT</span><br><strong><?= htmlspecialchars($patient['vitals']['weight']) ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-tachometer-alt"></i>
                            <div><span class="label">BLOOD PRESSURE</span><br><strong><?= htmlspecialchars($patient['vitals']['bp']) ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-heartbeat"></i>
                            <div><span class="label">CARDIAC RATE</span><br><strong><?= htmlspecialchars($patient['vitals']['cardiac_rate']) ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-thermometer-half"></i>
                            <div><span class="label">TEMPERATURE</span><br><strong><?= htmlspecialchars($patient['vitals']['temperature']) ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-lungs"></i>
                            <div><span class="label">RESPIRATORY RATE</span><br><strong><?= htmlspecialchars($patient['vitals']['respiratory_rate']) ?></strong></div>
                        </div>
                    </div>
                </div>
                <!-- You may add PHP loops for appointments and medical history as needed -->
                <!-- Example static content for illustration: -->
                <div class="summary-card appointment-section">
                    <div class="section-header">
                        <h2>Latest Appointment</h2>
                        <a href="#" class="view-more-btn">
                            <i class="fas fa-chevron-right"></i> View More
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2025-06-20</td>
                                    <td>09:30 AM</td>
                                    <td>General Check-up</td>
                                    <td>Completed</td>
                                    <td>All good</td>
                                </tr>
                                <tr>
                                    <td>2025-03-14</td>
                                    <td>10:15 AM</td>
                                    <td>Blood Test</td>
                                    <td>Completed</td>
                                    <td>Normal</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Medical History Example -->
                <div class="summary-card medical-history-section">
                    <h2>Medical History</h2>
                    <div class="medical-grid">
                        <div class="medical-card">
                            <h4>Past Medical Conditions</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Condition</th>
                                        <th>Year Diagnosed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Asthma</td><td>2005</td><td>Controlled</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="medical-card">
                            <h4>Chronic Illnesses</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Illness</th>
                                        <th>Year Diagnosed</th>
                                        <th>Management</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Hypertension</td><td>2022</td><td>Medication</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="medical-card">
                            <h4>Family History</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Family Member</th>
                                        <th>Condition</th>
                                        <th>Age Diagnosed</th>
                                        <th>Current Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Father</td><td>Diabetes</td><td>49</td><td>Managed</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="medical-card">
                            <h4>Surgical History</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Surgery</th>
                                        <th>Year</th>
                                        <th>Hospital</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Appendectomy</td><td>2010</td><td>City Hospital</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="medical-card">
                            <h4>Allergies</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Allergen</th>
                                        <th>Reaction</th>
                                        <th>Severity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Penicillin</td><td>Rash</td><td>Mild</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="medical-card">
                            <h4>Current Medications</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Prescribed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Lisinopril</td><td>10mg</td><td>Once daily</td><td>Dr. Smith</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        function toggleNav() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const topbarLogo = document.getElementById('topbarLogo');
            const menuIcon = document.getElementById('menuIcon');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            const isOpen = sidebar.classList.contains('open');
            if (topbarLogo) topbarLogo.style.display = isOpen ? 'none' : 'block';
            menuIcon.classList.toggle('fa-bars', !isOpen);
            menuIcon.classList.toggle('fa-times', isOpen);
        }
        function closeNav() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
            if (document.getElementById('topbarLogo')) document.getElementById('topbarLogo').style.display = 'block';
            const menuIcon = document.getElementById('menuIcon');
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
        }
    </script>
</body>
</html>