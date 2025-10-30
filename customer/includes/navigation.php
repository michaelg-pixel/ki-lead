<!-- Navigation -->
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="text-2xl font-bold text-purple-600">
                🚀 KI Lead-System
            </div>
            <div class="flex gap-6">
                <a href="dashboard.php" class="text-gray-600 hover:text-purple-600 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-purple-600 font-semibold' : ''; ?>">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                <a href="courses.php" class="text-gray-600 hover:text-purple-600 <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'text-purple-600 font-semibold' : ''; ?>">
                    <i class="fas fa-graduation-cap mr-2"></i> Kurse
                </a>
                <a href="freebies.php" class="text-gray-600 hover:text-purple-600 <?php echo basename($_SERVER['PHP_SELF']) == 'freebies.php' ? 'text-purple-600 font-semibold' : ''; ?>">
                    <i class="fas fa-gift mr-2"></i> Templates
                </a>
                <a href="my-freebies.php" class="text-gray-600 hover:text-purple-600 <?php echo basename($_SERVER['PHP_SELF']) == 'my-freebies.php' ? 'text-purple-600 font-semibold' : ''; ?>">
                    <i class="fas fa-folder mr-2"></i> Meine Freebies
                </a>
                <a href="tutorials.php" class="text-gray-600 hover:text-purple-600 <?php echo basename($_SERVER['PHP_SELF']) == 'tutorials.php' ? 'text-purple-600 font-semibold' : ''; ?>">
                    <i class="fas fa-question-circle mr-2"></i> Anleitungen
                </a>
                <a href="logout.php" class="text-red-600 hover:text-red-700">
                    <i class="fas fa-sign-out-alt mr-2"></i> Abmelden
                </a>
            </div>
        </div>
    </div>
</nav>