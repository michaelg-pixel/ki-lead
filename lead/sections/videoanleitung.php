<?php
/**
 * Lead Dashboard - Videoanleitung Sektion
 * Zeigt Video-Tutorials zum Empfehlungsprogramm
 */
if (!isset($lead) || !isset($pdo)) {
    die('Unauthorized');
}
?>

<div class="animate-fade-in-up opacity-0">
    <h2 class="text-3xl font-bold text-white mb-8">
        <i class="fas fa-video text-purple-400 mr-3"></i>
        Videoanleitung
    </h2>
    
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 shadow-xl border border-purple-500/20 mb-8">
        <div class="mb-6">
            <h3 class="text-white text-2xl font-bold mb-3">
                <i class="fas fa-play-circle text-green-400 mr-2"></i>
                So funktioniert das Empfehlungsprogramm
            </h3>
            <p class="text-gray-400 mb-6">
                In diesem Video erfährst du Schritt für Schritt, wie du mit unserem Empfehlungsprogramm 
                großartige Belohnungen verdienen kannst.
            </p>
        </div>
        
        <!-- Video Player Placeholder -->
        <div class="aspect-video bg-gray-900 rounded-xl overflow-hidden mb-6 border border-purple-500/20">
            <div class="w-full h-full flex items-center justify-center">
                <div class="text-center">
                    <i class="fas fa-video text-purple-400 text-6xl mb-4"></i>
                    <p class="text-gray-400">Video wird geladen...</p>
                    <p class="text-gray-500 text-sm mt-2">Das Tutorial-Video wird hier angezeigt</p>
                </div>
            </div>
        </div>
        
        <!-- Video Punkte -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-800/50 rounded-xl p-4 border border-purple-500/10">
                <div class="flex items-start gap-3">
                    <div class="bg-purple-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-share-alt text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-1">Schritt 1: Link teilen</h4>
                        <p class="text-gray-400 text-sm">Kopiere deinen persönlichen Empfehlungslink</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800/50 rounded-xl p-4 border border-purple-500/10">
                <div class="flex items-start gap-3">
                    <div class="bg-green-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-1">Schritt 2: Freunde einladen</h4>
                        <p class="text-gray-400 text-sm">Teile den Link mit deinen Freunden</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800/50 rounded-xl p-4 border border-purple-500/10">
                <div class="flex items-start gap-3">
                    <div class="bg-blue-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-1">Schritt 3: Empfehlungen sammeln</h4>
                        <p class="text-gray-400 text-sm">Deine Freunde registrieren sich über deinen Link</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800/50 rounded-xl p-4 border border-purple-500/10">
                <div class="flex items-start gap-3">
                    <div class="bg-yellow-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-gift text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-1">Schritt 4: Belohnungen erhalten</h4>
                        <p class="text-gray-400 text-sm">Verdiene automatisch tolle Belohnungen</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Weitere Tutorials -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Tutorial 1: Social Media -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden border border-purple-500/20 hover:border-purple-500 transition-all group shadow-xl">
            <div class="h-48 bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center relative overflow-hidden">
                <i class="fas fa-hashtag text-white text-6xl group-hover:scale-110 transition-transform duration-300"></i>
            </div>
            <div class="p-6">
                <h3 class="text-white font-bold text-xl mb-2">Social Media Sharing</h3>
                <p class="text-gray-400 text-sm mb-4">
                    Lerne, wie du deine Empfehlungen effektiv auf Social Media teilst
                </p>
                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl font-semibold transition-all">
                    <i class="fas fa-play-circle mr-2"></i>Video ansehen
                </button>
            </div>
        </div>
        
        <!-- Tutorial 2: KI Assistant -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden border border-purple-500/20 hover:border-purple-500 transition-all group shadow-xl">
            <div class="h-48 bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center relative overflow-hidden">
                <i class="fas fa-robot text-white text-6xl group-hover:scale-110 transition-transform duration-300"></i>
            </div>
            <div class="p-6">
                <h3 class="text-white font-bold text-xl mb-2">KI Social Assistant nutzen</h3>
                <p class="text-gray-400 text-sm mb-4">
                    Erstelle automatisch perfekte Posts mit unserem KI-Tool
                </p>
                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-xl font-semibold transition-all">
                    <i class="fas fa-play-circle mr-2"></i>Video ansehen
                </button>
            </div>
        </div>
        
        <!-- Tutorial 3: Belohnungen -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden border border-purple-500/20 hover:border-purple-500 transition-all group shadow-xl">
            <div class="h-48 bg-gradient-to-br from-yellow-600 to-orange-600 flex items-center justify-center relative overflow-hidden">
                <i class="fas fa-trophy text-white text-6xl group-hover:scale-110 transition-transform duration-300"></i>
            </div>
            <div class="p-6">
                <h3 class="text-white font-bold text-xl mb-2">Belohnungen maximieren</h3>
                <p class="text-gray-400 text-sm mb-4">
                    Tipps und Tricks, um das Maximum aus dem Programm herauszuholen
                </p>
                <button class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-xl font-semibold transition-all">
                    <i class="fas fa-play-circle mr-2"></i>Video ansehen
                </button>
            </div>
        </div>
    </div>
    
    <!-- FAQ Bereich -->
    <div class="mt-12 bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 shadow-xl border border-purple-500/20">
        <h3 class="text-white text-2xl font-bold mb-6">
            <i class="fas fa-question-circle text-purple-400 mr-2"></i>
            Häufig gestellte Fragen
        </h3>
        
        <div class="space-y-4">
            <div class="bg-gray-800/50 rounded-xl p-4 border border-purple-500/10">
                <div class="flex items-start gap-3">
                    <i class="fas fa-chevron-right text-purple-400 mt-1"></i>
                    <div>
                        <h4 class="text-white font-semibold mb-1">Wie viele Empfehlungen brauche ich für eine Belohnung?</h4>
                        <p class="text-gray-400 text-sm">Die Anzahl variiert je nach Belohnungsstufe. Die erste Belohnung gibt es meist schon ab 3 Empfehlungen.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800/50 rounded-xl p-4 border border-purple-500/10">
                <div class="flex items-start gap-3">
                    <i class="fas fa-chevron-right text-purple-400 mt-1"></i>
                    <div>
                        <h4 class="text-white font-semibold mb-1">Wann bekomme ich meine Belohnung?</h4>
                        <p class="text-gray-400 text-sm">Belohnungen werden automatisch vergeben, sobald du die erforderliche Anzahl an Empfehlungen erreicht hast.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800/50 rounded-xl p-4 border border-purple-500/10">
                <div class="flex items-start gap-3">
                    <i class="fas fa-chevron-right text-purple-400 mt-1"></i>
                    <div>
                        <h4 class="text-white font-semibold mb-1">Kann ich mehrere Belohnungen sammeln?</h4>
                        <p class="text-gray-400 text-sm">Ja! Du kannst alle Belohnungsstufen erreichen und jede einzelne Belohnung erhalten.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Action -->
    <div class="mt-8 text-center">
        <a href="?page=empfehlen<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
           class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all shadow-xl">
            <i class="fas fa-rocket"></i>
            Jetzt loslegen und empfehlen
        </a>
    </div>
</div>
