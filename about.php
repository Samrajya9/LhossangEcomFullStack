<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/authFunctions.php';

$pageTitle = "About Us - InfinityWaves";

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main>
    <section class="main-content-area">
        <div class="container">

            <!-- Section 1: About InfinityWaves Description -->
            <div class="about-intro">
                <h2 class="section-title">About InfinityWaves</h2>
                <p>
                    Founded on a deep-seated passion for pristine sound, InfinityWaves was born from a simple idea: to make high-fidelity audio accessible to everyone who appreciates it. We are a curated e-commerce platform dedicated to bringing you the world's finest home audio solutions, from powerful, floor-standing speakers to sleek, modern home theater systems.
                </p>
                <p>
                    Our mission is to elevate your listening experience. We believe that sound is not just heard—it's felt. That's why every product in our collection is handpicked for its exceptional quality, cutting-edge technology, and timeless design.
                </p>
            </div>

            <!-- Section 2: Our Team -->
            <div class="team-section">
                <h2 class="section-title">Meet Our Team</h2>
                <p class="section-subtitle">The passionate individuals behind the sound.</p>

                <div class="team-grid">
                    <!-- Team Member 1 -->
                    <div class="team-member-card">
                        <div class="team-member-photo">
                            <img src="<?=BASE_URL?>/assets/images/rohit.jpg" alt="Photo of Rohit Bist">
                        </div>
                        <h3>Mr. Rohit Bist</h3>
                        <p class="role">Designer</p>
                        <p class="specialty">Expert In Figma</p>
                    </div>

                    <!-- Team Member 2 -->
                    <div class="team-member-card">
                        <div class="team-member-photo">
                            <img src="<?=BASE_URL?>/assets/images/prajwal.jpg" alt="Photo of Prajwal Buddha">
                        </div>
                        <h3>Prajwal Buddha</h3>
                        <p class="role">FrontEnd Developer</p>
                        <p class="specialty">UI/UX Specialist</p>
                    </div>

                    <!-- Team Member 3 -->
                    <div class="team-member-card">
                        <div class="team-member-photo">
                            <img src="<?=BASE_URL?>/assets/images/lhosang.jpg" alt="Photo of Lhosang Lama">
                        </div>
                        <h3>Lhosang Lama</h3>
                        <p class="role">BackEnd Developer</p>
                        <p class="specialty">JavaScript Expert</p>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>