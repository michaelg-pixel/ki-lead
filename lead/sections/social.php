<?php
/**
 * Lead Dashboard - KI Social Assistant
 * Erstellt Social Media Posts für verschiedene Plattformen
 */
if (!isset($lead) || !isset($pdo) || !isset($selected_freebie)) {
    die('Unauthorized or no freebie selected');
}

// Referral-Link generieren
$referral_link = 'https://app.mehr-infos-jetzt.de/freebie/index.php?id=' . $selected_freebie['unique_id'] . '&ref=' . $lead['referral_code'];
?>

<div class="max-w-5xl mx-auto">
    
    <!-- Header -->
    <div class="mb-8 animate-fade-in-up opacity-0">
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4">
                    <i class="fas fa-robot text-white text-4xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white">KI Social Assistant</h2>
                    <p class="text-blue-100">Erstelle perfekte Social Media Posts mit KI</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-white">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                </span>
                <span class="text-sm font-semibold">KI-Powered • Instant • Kostenlos</span>
            </div>
        </div>
    </div>
    
    <!-- Platform Selection -->
    <div class="mb-8 animate-fade-in-up opacity-0" style="animation-delay: 0.1s;">
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
            <h3 class="text-white font-bold text-xl mb-4">
                <i class="fas fa-th text-purple-400 mr-2"></i>
                Wähle deine Plattform
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <button onclick="selectPlatform('facebook')" 
                        class="platform-btn bg-gray-700 hover:bg-blue-600 text-white p-4 rounded-xl transition-all border-2 border-transparent hover:border-blue-400"
                        data-platform="facebook">
                    <i class="fab fa-facebook text-3xl mb-2"></i>
                    <div class="text-sm font-semibold">Facebook</div>
                </button>
                
                <button onclick="selectPlatform('instagram')" 
                        class="platform-btn bg-gray-700 hover:bg-pink-600 text-white p-4 rounded-xl transition-all border-2 border-transparent hover:border-pink-400"
                        data-platform="instagram">
                    <i class="fab fa-instagram text-3xl mb-2"></i>
                    <div class="text-sm font-semibold">Instagram</div>
                </button>
                
                <button onclick="selectPlatform('twitter')" 
                        class="platform-btn bg-gray-700 hover:bg-sky-500 text-white p-4 rounded-xl transition-all border-2 border-transparent hover:border-sky-400"
                        data-platform="twitter">
                    <i class="fab fa-twitter text-3xl mb-2"></i>
                    <div class="text-sm font-semibold">Twitter/X</div>
                </button>
                
                <button onclick="selectPlatform('linkedin')" 
                        class="platform-btn bg-gray-700 hover:bg-blue-700 text-white p-4 rounded-xl transition-all border-2 border-transparent hover:border-blue-500"
                        data-platform="linkedin">
                    <i class="fab fa-linkedin text-3xl mb-2"></i>
                    <div class="text-sm font-semibold">LinkedIn</div>
                </button>
                
                <button onclick="selectPlatform('whatsapp')" 
                        class="platform-btn bg-gray-700 hover:bg-green-600 text-white p-4 rounded-xl transition-all border-2 border-transparent hover:border-green-400"
                        data-platform="whatsapp">
                    <i class="fab fa-whatsapp text-3xl mb-2"></i>
                    <div class="text-sm font-semibold">WhatsApp</div>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Post Generator -->
    <div id="postGenerator" class="hidden animate-fade-in-up">
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-white font-bold text-xl">
                    <i class="fas fa-magic text-purple-400 mr-2"></i>
                    Generiere deinen Post
                </h3>
                <button onclick="generatePost()" 
                        class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-all">
                    <i class="fas fa-sparkles mr-2"></i>
                    KI generieren
                </button>
            </div>
            
            <!-- Loading State -->
            <div id="loadingState" class="hidden text-center py-8">
                <div class="inline-block">
                    <i class="fas fa-circle-notch fa-spin text-purple-400 text-4xl mb-4"></i>
                    <p class="text-white font-semibold">KI erstellt deinen perfekten Post...</p>
                    <p class="text-gray-400 text-sm mt-2">Das dauert nur 2-3 Sekunden</p>
                </div>
            </div>
            
            <!-- Generated Post -->
            <div id="generatedPost" class="hidden">
                <div class="bg-gray-900 rounded-xl p-6 mb-4">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-purple-600 rounded-full w-12 h-12 flex items-center justify-center">
                                <i class="fas fa-user text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="text-white font-semibold"><?php echo htmlspecialchars($lead['name']); ?></div>
                                <div class="text-gray-400 text-sm" id="platformLabel">Facebook</div>
                            </div>
                        </div>
                        <i class="fas fa-ellipsis-h text-gray-400"></i>
                    </div>
                    
                    <div id="postContent" class="text-white text-base leading-relaxed mb-4 whitespace-pre-wrap">
                        <!-- Post Text wird hier eingefügt -->
                    </div>
                    
                    <div class="bg-gray-800 rounded-lg p-4 mb-4">
                        <div class="text-gray-400 text-sm mb-2">📎 Dein Empfehlungs-Link:</div>
                        <div class="text-blue-400 text-sm break-all"><?php echo htmlspecialchars($referral_link); ?></div>
                    </div>
                    
                    <div class="flex items-center gap-4 pt-4 border-t border-gray-700">
                        <button class="text-gray-400 hover:text-blue-400 transition-colors">
                            <i class="far fa-thumbs-up mr-1"></i> Gefällt mir
                        </button>
                        <button class="text-gray-400 hover:text-green-400 transition-colors">
                            <i class="far fa-comment mr-1"></i> Kommentieren
                        </button>
                        <button class="text-gray-400 hover:text-purple-400 transition-colors">
                            <i class="far fa-share-square mr-1"></i> Teilen
                        </button>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-3">
                    <button onclick="copyPost()" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                        <i class="fas fa-copy mr-2"></i>
                        Post kopieren
                    </button>
                    <button onclick="generatePost()" 
                            class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Neu generieren
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tips -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-yellow-500/20 animate-fade-in-up opacity-0" style="animation-delay: 0.2s;">
        <h3 class="text-white font-bold text-xl mb-4">
            <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>
            Tipps für mehr Erfolg
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-900/50 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <div class="bg-green-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                    <div>
                        <div class="text-white font-semibold mb-1">Beste Posting-Zeiten</div>
                        <div class="text-gray-400 text-sm">Mo-Fr: 12-15 Uhr und 19-21 Uhr<br>Sa-So: 10-12 Uhr und 18-20 Uhr</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-900/50 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <div class="bg-blue-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-hashtag text-white"></i>
                    </div>
                    <div>
                        <div class="text-white font-semibold mb-1">Hashtags nutzen</div>
                        <div class="text-gray-400 text-sm">3-5 relevante Hashtags erhöhen die Reichweite deutlich</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-900/50 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <div class="bg-purple-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div>
                        <div class="text-white font-semibold mb-1">Persönlich bleiben</div>
                        <div class="text-gray-400 text-sm">Teile deine eigene Geschichte und Erfahrungen</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-900/50 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <div class="bg-pink-600 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-heart text-white"></i>
                    </div>
                    <div>
                        <div class="text-white font-semibold mb-1">Authentisch sein</div>
                        <div class="text-gray-400 text-sm">Echte Empfehlungen wirken am besten</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
let currentPlatform = '';
const freebieTitle = <?php echo json_encode($selected_freebie['title']); ?>;
const freebieDescription = <?php echo json_encode($selected_freebie['description']); ?>;
const referralLink = <?php echo json_encode($referral_link); ?>;
let lastGeneratedPost = '';

function selectPlatform(platform) {
    currentPlatform = platform;
    
    // Alle Buttons zurücksetzen
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-purple-500', 'bg-purple-600', 'scale-105');
    });
    
    // Aktiven Button highlighten
    const activeBtn = document.querySelector(`[data-platform="${platform}"]`);
    activeBtn.classList.add('ring-2', 'ring-purple-500', 'scale-105');
    
    // Generator anzeigen
    document.getElementById('postGenerator').classList.remove('hidden');
    document.getElementById('postGenerator').classList.add('animate-fade-in-up');
    
    // Scroll to generator
    setTimeout(() => {
        document.getElementById('postGenerator').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

async function generatePost() {
    if (!currentPlatform) {
        alert('Bitte wähle zuerst eine Plattform aus!');
        return;
    }
    
    // Loading anzeigen
    document.getElementById('generatedPost').classList.add('hidden');
    document.getElementById('loadingState').classList.remove('hidden');
    
    // Simuliere KI-Generierung (in Production: echte API-Call)
    setTimeout(() => {
        const post = generateAIPost(currentPlatform);
        lastGeneratedPost = post;
        
        document.getElementById('postContent').textContent = post;
        document.getElementById('platformLabel').textContent = getPlatformLabel(currentPlatform);
        
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('generatedPost').classList.remove('hidden');
    }, 2000);
}

function generateAIPost(platform) {
    const templates = {
        facebook: [
            `🎁 Ich habe gerade etwas Geniales entdeckt!\n\n"${freebieTitle}" hat mir wirklich weitergeholfen und ich möchte es gerne mit euch teilen.\n\n✨ Was du bekommst:\n${freebieDescription}\n\n👉 Hol es dir kostenlos hier:\n${referralLink}\n\n#Empfehlung #Kostenlos #${freebieTitle.replace(/\s+/g, '')}`,
            
            `Hey Leute! 👋\n\nIch bin gerade auf "${freebieTitle}" gestoßen und muss sagen: Das ist echt nützlich!\n\n${freebieDescription}\n\nFalls du auch Interesse hast, schau mal hier vorbei:\n${referralLink}\n\nViel Erfolg! 🚀`,
            
            `💡 Tipp des Tages:\n\nIch habe "${freebieTitle}" ausprobiert und bin begeistert!\n\nPerfekt für alle, die ${freebieDescription.toLowerCase()}\n\n✅ Kostenlos\n✅ Sofort verfügbar\n✅ Einfach zu nutzen\n\nHier gehts zum kostenlosen Download:\n${referralLink}`
        ],
        instagram: [
            `🌟 Heute möchte ich etwas mit euch teilen!\n\n"${freebieTitle}" – ${freebieDescription}\n\n📲 Link in Bio!\n\n#freebie #kostenlos #empfehlung #${freebieTitle.replace(/\s+/g, '').toLowerCase()}`,
            
            `✨ Neu entdeckt und direkt verliebt! 💜\n\n${freebieTitle} ist genau das, was ich gesucht habe.\n\n${freebieDescription}\n\n👆 Link zum Freebie findest du in meiner Bio!\n\n#gratis #tipp #musthave`,
            
            `💎 Geheimtipp Alert!\n\nHabt ihr schon von "${freebieTitle}" gehört?\n\n${freebieDescription}\n\nUnd das Beste: Komplett kostenlos! 🎁\n\n🔗 Mehr Infos im Link in meiner Bio\n\n#freebie #empfehlung #kostenlos`
        ],
        twitter: [
            `🎁 Gerade "${freebieTitle}" entdeckt!\n\n${freebieDescription}\n\n✅ Kostenlos\n✅ Sofort verfügbar\n\n👉 ${referralLink}\n\n#Freebie #Kostenlos`,
            
            `💡 Tipp: "${freebieTitle}"\n\n${freebieDescription.substring(0, 100)}...\n\nKostenlos hier: ${referralLink}\n\n#Empfehlung`,
            
            `🚀 Kann "${freebieTitle}" nur empfehlen!\n\nPerfekt für alle, die ${freebieDescription.substring(0, 80)}...\n\n🔗 ${referralLink}`
        ],
        linkedin: [
            `Ich möchte heute eine Ressource mit euch teilen, die mir persönlich sehr weitergeholfen hat:\n\n"${freebieTitle}"\n\n${freebieDescription}\n\nDiese Ressource ist kostenlos verfügbar und könnte auch für euch interessant sein.\n\nMehr Informationen findet ihr hier:\n${referralLink}\n\n#ProfessionalDevelopment #Empfehlung #Weiterbildung`,
            
            `Kolleg:innen aufgepasst! 📢\n\nIch bin auf "${freebieTitle}" gestoßen und finde es sehr wertvoll.\n\n${freebieDescription}\n\nFalls ihr daran interessiert seid, findet ihr hier weitere Informationen:\n${referralLink}\n\n#Business #Productivity #Growth`
        ],
        whatsapp: [
            `Hey! 👋\n\nIch habe gerade "${freebieTitle}" entdeckt und dachte, das könnte dich auch interessieren!\n\n${freebieDescription}\n\nIst komplett kostenlos:\n${referralLink}\n\nViel Spaß damit! 😊`,
            
            `Hi!\n\nKleiner Tipp: "${freebieTitle}" ist echt hilfreich!\n\n${freebieDescription}\n\nKannst du hier kostenlos bekommen:\n${referralLink}\n\nLG! ✌️`
        ]
    };
    
    const platformTemplates = templates[platform] || templates.facebook;
    return platformTemplates[Math.floor(Math.random() * platformTemplates.length)];
}

function getPlatformLabel(platform) {
    const labels = {
        facebook: 'Facebook',
        instagram: 'Instagram',
        twitter: 'Twitter/X',
        linkedin: 'LinkedIn',
        whatsapp: 'WhatsApp'
    };
    return labels[platform] || platform;
}

function copyPost() {
    const postText = lastGeneratedPost;
    
    navigator.clipboard.writeText(postText).then(() => {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i>Post kopiert!';
        btn.classList.remove('bg-green-600', 'hover:bg-green-700');
        btn.classList.add('bg-green-700');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('bg-green-700');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');
        }, 2000);
    }).catch(err => {
        alert('Bitte kopiere den Post manuell');
    });
}
</script>