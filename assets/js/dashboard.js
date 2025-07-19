// Constants
const API_BASE_URL = 'https://games.teluapp.org/8puzzle/backend/api';
const API_ENDPOINTS = {
    leaderboard: `${API_BASE_URL}/leaderboard.php`,
    profile: `${API_BASE_URL}/users/get_profile.php`,
    weeklyChallenge: `${API_BASE_URL}/challenges/get_weekly_challenge.php`,
    updateChallenge: `${API_BASE_URL}/challenges/update_challenges_progress.php`,
    saveScore: `${API_BASE_URL}/scores/save_score.php`
};


// DOM Elements  
const elements = {
    totalPlayers: document.getElementById('totalPlayers'),
    highestScore: document.getElementById('highestScore'),
    averageScore: document.getElementById('averageScore'), 
    achievementsContainer: document.getElementById('achievementsContainer'),
    challengeInfo: document.getElementById('challengeInfo'),
    leaderboardBody: document.getElementById('leaderboardBody'),
    scoreChart: document.getElementById('scoreChart'),
    challengeContainer: document.getElementById('challengeContainer')
};

// Enhanced notification system with persistent tracking
const notificationSystem = {
    queues: {
        achievement: [],
        challenge: []
    },
    processing: {
        achievement: false,
        challenge: false
    },

    // Load shown notifications from localStorage
    getShownNotifications() {
        try {
            const saved = localStorage.getItem('shownNotifications');
            return saved ? new Set(JSON.parse(saved)) : new Set();
        } catch (e) {
            console.error('Error loading shown notifications:', e);
            return new Set();
        }
    },

    // Save shown notifications to localStorage
    saveShownNotifications(notifications) {
        try {
            localStorage.setItem('shownNotifications', 
                JSON.stringify([...notifications]));
        } catch (e) {
            console.error('Error saving shown notifications:', e);
        }
    },

    init() {
        // Create containers if they don't exist
        if (!document.getElementById('notification-container-achievement')) {
            const achievementContainer = document.createElement('div');
            achievementContainer.id = 'notification-container-achievement';
            document.body.appendChild(achievementContainer);
        }
        
        if (!document.getElementById('notification-container-challenge')) {
            const challengeContainer = document.createElement('div');
            challengeContainer.id = 'notification-container-challenge';
            document.body.appendChild(challengeContainer);
        }

        // Initialize shown notifications from localStorage
        this.shownNotifications = this.getShownNotifications();
    },

    async processQueue(type) {
        if (this.processing[type] || this.queues[type].length === 0) return;
        
        this.processing[type] = true;
        
        while (this.queues[type].length > 0) {
            const data = this.queues[type].shift();
            await this.showNotification(type, data);
            await new Promise(resolve => setTimeout(resolve, 5500));
        }
        
        this.processing[type] = false;
    },

    generateNotificationKey(type, data) {
        if (type === 'achievement') {
            return `achievement-${data.title}`;
        } else if (type === 'challenge') {
            return `challenge-${data.title}-${new Date().toISOString().split('T')[0]}`;
        }
        return null;
    },

    async showNotification(type, data) {
        return new Promise(resolve => {
            const containerId = type === 'achievement' ? 
                'notification-container-achievement' : 
                'notification-container-challenge';
            
            const container = document.getElementById(containerId);
            if (!container) {
                resolve();
                return;
            }

            const notification = document.createElement('div');
            notification.className = `game-notification ${type}-notification`;
            
            let content = '';
            if (type === 'achievement') {
                content = `
                    <div class="notification-content">
                        <div class="notification-icon">${data.icon || '🏆'}</div>
                        <div class="notification-text">
                            <h4>Achievement Unlocked!</h4>
                            <p>${data.title}</p>
                            <p>${data.description || ''}</p>
                            ${data.xp_reward ? `<span class="xp-reward">+${data.xp_reward} XP</span>` : ''}
                        </div>
                    </div>`;
            } else if (type === 'challenge') {
                content = `
                    <div class="notification-content">
                        <div class="notification-icon">
                            ${getChallengeIcon(data.challenge_type)}
                        </div>
                        <div class="notification-text">
                            <h4>Challenge Complete!</h4>
                            <p>${data.title}</p>
                            <span class="xp-reward">+${data.reward_xp} XP</span>
                        </div>
                    </div>`;
            }

            notification.innerHTML = content;
            container.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    if (container.contains(notification)) {
                        container.removeChild(notification);
                    }
                    resolve();
                }, 300);
            }, 5000);
        });
    },

    show(type, data) {
        const notificationKey = this.generateNotificationKey(type, data);
        
        // Check if this notification has already been shown
        if (notificationKey && this.shownNotifications.has(notificationKey)) {
            return; // Skip if already shown
        }

        // Add to shown notifications and save to localStorage
        if (notificationKey) {
            this.shownNotifications.add(notificationKey);
            this.saveShownNotifications(this.shownNotifications);
        }

        // Add to queue and process
        this.queues[type].push(data);
        this.processQueue(type);
    }
};

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    notificationSystem.init();
    initializeDashboard();
    initializeAutoRefresh();

    // Add event listener for challenge updates if needed
    if (elements.challengeInfo) {
        elements.challengeInfo.addEventListener('click', (e) => {
            const challengeItem = e.target.closest('.challenge-item');
            if (challengeItem && !challengeItem.classList.contains('completed')) {
                const challengeId = challengeItem.id.split('-')[1];
                console.log('Challenge clicked:', challengeId);
            }
        });
    }
});

// Main Dashboard Initialization
async function initializeDashboard() {
    try {
        // First, check if all required elements exist
        if (!elements.leaderboardBody) {
            console.error('Leaderboard body element not found');
        }
        
        if (!elements.scoreChart) {
            console.error('Score chart element not found');
        }
        
        if (!elements.achievementsContainer) {
            console.error('Achievements container element not found');
        }
        
        if (!elements.challengeContainer) {
            console.error('Challenge container element not found');
        }
        
        // Fetch data from APIs
        const [profileResponse, leaderboardResponse, challengeResponse] = await Promise.all([
            fetch(API_ENDPOINTS.profile, { credentials: 'include' })
                .catch(err => {
                    console.error('Error fetching profile:', err);
                    return { json: () => Promise.resolve({ success: false }) };
                }),
            fetch(API_ENDPOINTS.leaderboard)
                .catch(err => {
                    console.error('Error fetching leaderboard:', err);
                    return { json: () => Promise.resolve({ success: false }) };
                }),
            fetch(API_ENDPOINTS.weeklyChallenge, { credentials: 'include' })
                .catch(err => {
                    console.error('Error fetching challenges:', err);
                    return { json: () => Promise.resolve({ success: false }) };
                })
        ]);

        const [profileData, leaderboardData, challengeData] = await Promise.all([
            profileResponse.json().catch(err => {
                console.error('Error parsing profile data:', err);
                return { success: false };
            }),
            leaderboardResponse.json().catch(err => {
                console.error('Error parsing leaderboard data:', err);
                return { success: false };
            }),
            challengeResponse.json().catch(err => {
                console.error('Error parsing challenge data:', err);
                return { success: false };
            })
        ]);

        // Update UI with data
        if (leaderboardData.success) {
            updateStatistics(leaderboardData.stats);
            updateLeaderboard(leaderboardData);
            
            // Only update chart if the element exists and data is available
            if (elements.scoreChart && document.getElementById('scoreChart')) {
                setTimeout(() => {
                    updateChart(leaderboardData);
                }, 100); // Small delay to ensure DOM is ready
            }
        }

        if (profileData.success && profileData.achievements?.length > 0 && elements.achievementsContainer) {
            initializeAchievements(profileData.achievements);
        }

        if (challengeData.success && elements.challengeContainer) {
            updateWeeklyChallenge(challengeData);
            if (challengeData.challenges) {
                startChallengeCountdown(challengeData.challenges);
            }
        }

    } catch (error) {
        console.error('Error initializing dashboard:', error);
        handleError(error);
    }
}

// Initialize Achievements
function initializeAchievements(achievements) {
    if (!achievements?.length) return;

    achievements.forEach(achievement => {
        if (achievement.unlocked) {
            notificationSystem.show('achievement', {
                icon: achievement.icon,
                title: achievement.title,
                description: achievement.description,
                xp_reward: 100
            });
        }
    });

    if (elements.achievementsContainer) {
        elements.achievementsContainer.innerHTML = achievements.map(achievement => {
            const progressPercent = Math.min(100, (achievement.progress / achievement.target) * 100);
            
            return `
                <div class="achievement-card ${!achievement.unlocked ? 'locked' : ''}">
                    <div class="achievement-icon">
                        ${achievement.icon}
                    </div>
                    <h3>${achievement.title}</h3>
                    <div class="achievement-description">${achievement.description}</div>
                    
                    ${!achievement.unlocked ? `
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progressPercent}%"></div>
                        </div>
                        <div class="progress-text">${achievement.progress}/${achievement.target}</div>
                    ` : ''}
                    
                    ${achievement.achieved_at ? 
                        `<div class="achievement-date">Achieved: ${new Date(achievement.achieved_at).toLocaleDateString()}</div>` 
                        : ''
                    }
                    <span class="status-${achievement.unlocked ? 'unlocked' : 'locked'}">
                        ${achievement.unlocked ? 'Unlocked' : 'Locked'}
                    </span>
                </div>
            `;
        }).join('');
    }
}

// Update Leaderboard
function getAchievementBadge(title) {
    const badges = {
        'First Victory': { icon: '🏆', color: '#FFD700' },
        'Dedicated Player': { icon: '⭐', color: '#3498db' },
        'Speed Demon': { icon: '⚡', color: '#9b59b6' },
        'Perfect Solver': { icon: '🎯', color: '#2ecc71' },
        'Streak Master': { icon: '🔥', color: '#e74c3c' },
        'Pro Player': { icon: '👑', color: '#f1c40f' }
    };
    return badges[title] || { icon: '🎮', color: '#95a5a6' };
}

// Update Leaderboard
function updateLeaderboard(data) {
    if (!data.leaderboard?.length) return;

    const rows = data.leaderboard.map((player, index) => {
        const rankClass = index < 3 ? `rank-${index + 1}` : '';
        
        const achievementsHtml = player.achievements?.map(ach => {
            const badge = getAchievementBadge(ach.title);
            return `
                <span class="achievement-badge" 
                      title="${ach.description}"
                      style="background: ${badge.color}20; color: ${badge.color}; border: 1px solid ${badge.color}40;">
                    ${badge.icon} ${ach.title}
                </span>
            `;
        }).join('') || '';

        const rankBadgeStyle = index < 3 
            ? getRankBadgeStyle(index) 
            : 'background: #6c757d;';

        // Generate difficulty badge with appropriate class
        const difficulty = player.difficulty || 'easy';
        const difficultyBadge = `
            <span class="difficulty-badge difficulty-${difficulty.toLowerCase()}">
                ${difficulty}
            </span>
        `;

        return `
            <tr class="leaderboard-row ${rankClass}">
                <td>
                    <span class="rank-badge" style="${rankBadgeStyle}">
                        ${index + 1}
                    </span>
                </td>
                <td>
                    <div class="player-status">
                        <div class="player-info">
                            <span class="player-name">${player.username}</span>
                        </div>
                    </div>
                </td>
                <td>
                    ${difficultyBadge}
                </td>
                <td>${player.steps}</td>
                <td>${player.completion_time}s</td>
                <td>
                    <div class="achievement-badges">
                        ${achievementsHtml}
                    </div>
                </td>
                <td>
                    ${player.streak > 0 ? `
                        <span class="streak-badge">
                            <i class="fas fa-fire streak-flame"></i>
                            ${player.streak}
                        </span>
                    ` : '-'}
                </td>
                <td>
                    <div class="challenge-stats">
                        ${player.completed_challenges > 0 ? `
                            <div class="challenge-summary">
                                <span class="challenge-count" title="Completed Challenges">
                                    <i class="fas fa-trophy"></i> ${player.completed_challenges}
                                </span>
                                <span class="challenge-points" title="Challenge XP">
                                    <i class="fas fa-star"></i> ${player.challenge_xp}
                                </span>
                            </div>
                        ` : '-'}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    elements.leaderboardBody.innerHTML = rows;
}

// Update Weekly Challenge
function updateWeeklyChallenge(data) {
    if (!data?.challenges?.length || !elements.challengeContainer) return;

    data.challenges.forEach(challenge => {
        if (challenge.is_completed) {
            notificationSystem.show('challenge', {
                challenge_type: challenge.challenge_type,
                title: challenge.title,
                reward_xp: challenge.reward_xp
            });
        }
    });

    const challengesHtml = data.challenges.map(challenge => {
        const progressPercent = Math.min(100, (challenge.user_progress / challenge.target) * 100);
        
        return `
            <div class="challenge-item ${challenge.is_completed ? 'completed' : ''}" 
                 id="challenge-${challenge.id}">
                <div class="challenge-header">
                    <div class="challenge-icon-container">
                        <div class="challenge-icon">
                            ${getChallengeIcon(challenge.challenge_type)}
                        </div>
                        <div>
                            <h3 class="challenge-title">${challenge.title}</h3>
                            <p class="challenge-description">${challenge.description}</p>
                        </div>
                    </div>
                    ${challenge.is_completed ? `
                        <div class="challenge-reward">
                            <i class="fas fa-star"></i>
                            +${challenge.reward_xp} XP
                        </div>
                    ` : ''}
                </div>
                
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progressPercent}%"></div>
                    </div>
                    <div class="progress-status">
                        <span class="status-text">Progress</span>
                        <span class="status-text ${challenge.is_completed ? 'completed' : ''}">
                            ${challenge.is_completed ? 'Completed' : `${challenge.user_progress}/${challenge.target}`}
                        </span>
                    </div>
                </div>

                <div class="time-remaining" id="timer-${challenge.id}">
                    <i class="fas fa-clock"></i>
                    <span>${challenge.time_remaining.days}d ${challenge.time_remaining.hours}h remaining</span>
                </div>
            </div>
        `;
    }).join('');

    elements.challengeContainer.innerHTML = challengesHtml;
}

// Function to handle challenge countdown
function startChallengeCountdown(challenges) {
    challenges.forEach(challenge => {
        const timerElement = document.getElementById(`timer-${challenge.id}`);
        if (!timerElement) return;

        let totalSeconds = (challenge.time_remaining.days * 24 * 60 * 60) +
                         (challenge.time_remaining.hours * 60 * 60) +
                         (challenge.time_remaining.minutes * 60);

        const timerId = `countdown-${challenge.id}`;
        if (window[timerId]) {
            clearInterval(window[timerId]);
        }

        window[timerId] = setInterval(() => {
            if (totalSeconds <= 0) {
                clearInterval(window[timerId]);
                refreshChallenges();
                return;
            }

            const days = Math.floor(totalSeconds / (24 * 60 * 60));
            const hours = Math.floor((totalSeconds % (24 * 60 * 60)) / (60 * 60));
            const minutes = Math.floor((totalSeconds % (60 * 60)) / 60);
            const seconds = totalSeconds % 60;

            timerElement.innerHTML = `
                <i class="fas fa-clock"></i>
                <span>${days}d ${hours}h ${minutes}m ${seconds}s</span>
            `;

            totalSeconds--;
        }, 1000);
    });
}

// Score Submission Handler
async function handleScoreSubmission(scoreData) {
    try {
        const response = await fetch(API_ENDPOINTS.saveScore, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(scoreData),
            credentials: 'include'
        });

        const data = await response.json();
        if (data.success) {
            // Refresh achievements to check for new unlocks
            const profileResponse = await fetch(API_ENDPOINTS.profile, { 
                credentials: 'include' 
            });
            const profileData = await profileResponse.json();
            
            if (profileData.success && profileData.achievements?.length > 0) {
                initializeAchievements(profileData.achievements);
            }
            // Show notifications for completed challenges
            if (data.challenges_completed?.length > 0) {
                data.challenges_completed.forEach(challenge => {
                    if (challenge.is_completed) {
                        notificationSystem.show('challenge', challenge);
                    }
                });
            }

            // Refresh challenges after score submission
            refreshChallenges();
        }
    } catch (error) {
        console.error('Error saving score:', error);
    }
}

// Function to refresh challenges
async function refreshChallenges() {
    try {
        const response = await fetch(API_ENDPOINTS.weeklyChallenge, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            updateWeeklyChallenge(data);
            startChallengeCountdown(data.challenges);
        }
    } catch (error) {
        console.error('Error refreshing challenges:', error);
    }
}

// Update challenges progress
async function updateChallengeProgress(challengeId, progress) {
    try {
        const response = await fetch(API_ENDPOINTS.updateChallenge, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                challenge_id: challengeId,
                progress: progress
            })
        });
        
        const data = await response.json();
        if (data.success) {
            refreshChallenges();
        }
    } catch (error) {
        console.error('Error updating challenge progress:', error);
    }
}

// Helper Functions
function getChallengeIcon(type) {
    const icons = {
        'ACHIEVEMENT_HUNTER': '🏆',
        'SPEED_MASTER': '⚡',
        'PERFECT_SOLVER': '🎯',
        'DAILY_PLAYER': '📅',
        'HARD_MODE_MASTER': '💪',
        'PRECISION_KING': '🎮'
    };
    return icons[type] || '⭐';
}

function getRankBadgeStyle(index) {
    switch(index) {
        case 0: return 'background: linear-gradient(135deg, #ffd700, #ffa500);';
        case 1: return 'background: linear-gradient(135deg, #C0C0C0, #A9A9A9);';
        case 2: return 'background: linear-gradient(135deg, #CD7F32, #8B4513);';
        default: return 'background: #6c757d;';
    }
}

function handleError(error) {
    console.error('Dashboard error:', error);
}

// Update Statistics
function updateStatistics(stats) {
    if (!stats) return;
    elements.totalPlayers.textContent = stats.total_players || '0';
    elements.highestScore.textContent = stats.best_score || '0';
    elements.averageScore.textContent = stats.average_score?.toFixed(1) || '0';
}

// Enhanced Chart Logic - Replace your existing updateChart function with this one

// Chart Logic
let currentChart = null;

function updateChart(data) {
    if (!data.leaderboard?.length) return;
    
    const ctx = elements.scoreChart?.getContext('2d');
    if (!ctx) {
        console.error('Score chart canvas context not available');
        return;
    }
    
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

    // Safely destroy the existing chart if it exists
    if (currentChart instanceof Chart) {
        try {
            currentChart.destroy();
            currentChart = null;
        } catch (e) {
            console.error('Error destroying chart:', e);
        }
    }
    
    try {
        // Sort players by steps (ascending - lower is better)
        // Limit to top 5 performers for better visibility
        const sortedData = [...data.leaderboard]
            .sort((a, b) => a.steps - b.steps)
            .slice(0, 5);
        
        // Define a better color palette
        const colorPalette = {
            blue: {
                primary: '#4361ee',
                secondary: '#4895ef'
            },
            green: {
                primary: '#4cc9f0',
                secondary: '#2ecc71'
            },
            yellow: {
                primary: '#ffd60a',
                secondary: '#f9d423'
            },
            purple: {
                primary: '#3f37c9',
                secondary: '#9b59b6'
            },
            red: {
                primary: '#ef233c',
                secondary: '#e74c3c'
            }
        };
        
        // Generate gradient backgrounds for bars
        const gradients = sortedData.map((_, index) => {
            const gradient = ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);
            
            switch (index % 5) {
                case 0:
                    gradient.addColorStop(0, colorPalette.blue.primary);
                    gradient.addColorStop(1, colorPalette.blue.secondary);
                    return gradient;
                case 1:
                    gradient.addColorStop(0, colorPalette.green.primary);
                    gradient.addColorStop(1, colorPalette.green.secondary);
                    return gradient;
                case 2:
                    gradient.addColorStop(0, colorPalette.yellow.primary);
                    gradient.addColorStop(1, colorPalette.yellow.secondary);
                    return gradient;
                case 3:
                    gradient.addColorStop(0, colorPalette.purple.primary);
                    gradient.addColorStop(1, colorPalette.purple.secondary);
                    return gradient;
                case 4:
                    gradient.addColorStop(0, colorPalette.red.primary);
                    gradient.addColorStop(1, colorPalette.red.secondary);
                    return gradient;
                default:
                    return '#4361ee';
            }
        });
        
        // Generate border colors
        const borderColors = sortedData.map((_, index) => {
            switch (index % 5) {
                case 0: return colorPalette.blue.primary;
                case 1: return colorPalette.green.primary;
                case 2: return colorPalette.yellow.primary;
                case 3: return colorPalette.purple.primary;
                case 4: return colorPalette.red.primary;
                default: return '#4361ee';
            }
        });
        
        // Create the chart
        currentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sortedData.map(player => player.username),
                datasets: [{
                    label: 'Steps to Complete',
                    data: sortedData.map(player => player.steps),
                    backgroundColor: gradients,
                    borderColor: borderColors,
                    borderWidth: 1,
                    borderRadius: 8,
                    barThickness: 30,
                    maxBarThickness: 40
                }]
            },
            options: {
                indexAxis: 'y',  // Horizontal bar chart
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 20,
                        top: this.title ? 30 : 20,
                        bottom: 10
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(226, 232, 240, 0.6)',
                            borderDash: [5, 5],
                            drawBorder: false
                        },
                        ticks: {
                            color: '#8d99ae',
                            font: {
                                family: "'Poppins', sans-serif",
                                size: 12
                            },
                            padding: 8,
                            callback: function(value) {
                                return value % 1 === 0 ? value : '';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Number of Steps (Lower is Better)',
                            color: '#2b2d42',
                            font: {
                                family: "'Poppins', sans-serif",
                                size: 14,
                                weight: '500'
                            },
                            padding: { top: 10, bottom: 10 }
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#2b2d42',
                            font: {
                                family: "'Poppins', sans-serif",
                                weight: '500',
                                size: 13
                            },
                            padding: 12
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2b2d42',
                        bodyColor: '#2b2d42',
                        borderColor: '#e9ecef',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            family: "'Poppins', sans-serif",
                            weight: '600',
                            size: 14
                        },
                        bodyFont: {
                            family: "'Poppins', sans-serif",
                            size: 13
                        },
                        displayColors: true,
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                // Find the corresponding player
                                const player = sortedData[context.dataIndex];
                                
                                // Create formatted labels
                                return [
                                    `Steps: ${player.steps}`,
                                    `Time: ${player.completion_time}s`,
                                    `Difficulty: ${player.difficulty || 'Easy'}`
                                ];
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Player Performance Comparison',
                        color: '#2b2d42',
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 18,
                            weight: '600'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart',
                    delay: function(context) {
                        // Stagger animations by data index
                        return context.dataIndex * 100;
                    }
                },
                hover: {
                    mode: 'nearest',
                    intersect: false
                }
            }
        });
        
        // Add event listener for animations
        ctx.canvas.addEventListener('mousemove', () => {
            // Add hover animation classes if needed
        });
        
    } catch (error) {
        console.error('Error creating performance chart:', error);
        // Fallback to simpler chart if there's an error
        createFallbackChart(ctx, data);
    }
}

// Fallback chart in case of error
function createFallbackChart(ctx, data) {
    try {
        const sortedData = [...data.leaderboard]
            .sort((a, b) => a.steps - b.steps)
            .slice(0, 5);
            
        currentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sortedData.map(player => player.username),
                datasets: [{
                    label: 'Steps',
                    data: sortedData.map(player => player.steps),
                    backgroundColor: 'rgba(67, 97, 238, 0.7)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Player Performance'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating fallback chart:', error);
        
        // If fallback chart also fails, show error message
        if (ctx && ctx.canvas) {
            ctx.font = "16px 'Poppins', sans-serif";
            ctx.fillStyle = "#2b2d42";
            ctx.textAlign = "center";
            ctx.fillText("Could not load performance chart", ctx.canvas.width / 2, ctx.canvas.height / 2);
        }
    }
}

// Auto Refresh Logic
function initializeAutoRefresh() {
    // Regular updates every 30 seconds
    setInterval(async () => {
        try {
            const [leaderboardResponse] = await Promise.all([
                fetch(API_ENDPOINTS.leaderboard),
                fetchAndUpdateAchievements()
            ]);
            
            const leaderboardData = await leaderboardResponse.json();
            
            if (leaderboardData.success) {
                updateLeaderboard(leaderboardData);
                updateStatistics(leaderboardData.stats);
                if (elements.scoreChart) {
                    updateChart(leaderboardData);
                }
            }
        } catch (error) {
            console.error('Auto-refresh error:', error);
        }
    }, 30000);

    // Weekly challenges update every 15 seconds
    setInterval(refreshChallenges, 15000);
}

// Initialize everything
async function fetchAndUpdateAchievements() {
    try {
        const response = await fetch(`${API_BASE_URL}/users/get_profile.php`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.achievements?.length > 0) {
            initializeAchievements(data.achievements);
        }
    } catch (error) {
        console.error('Error fetching achievements:', error);
    }
}