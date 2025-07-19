const API_BASE_URL = 'https://games.teluapp.org/8puzzle/backend/api';
const gameData = {
    gameProgress: {
        async saveGameResult(result) {
            try {
                // Pastikan data yang diperlukan ada
                if (!result.steps || !result.timeTaken || !result.level) {
                    console.error('Missing data for game result:', result);
                    return;
                }
 
                    const response = await fetch('${API_BASE_URL}/scores/save_score.php', {                    
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', 
                    },
                    body: JSON.stringify({
                        steps: result.steps,
                        completion_time: result.timeTaken,
                        level: result.level
                    }),
                    credentials: 'include'
                });
 
                const data = await response.json();
                if (data.success) {
                    // Tambahkan pengecekan challenge completed dan show notification
                    if (data.challenge_completed) {
                        showChallengeNotification(
                            data.challenge_completed,
                            data.challenge_completed.xp_earned
                        );
                    }
                    await this.updateDashboard();
                    return data;
                }
                throw new Error(data.error || 'Failed to save score');
            } catch (error) {
                console.error('Error saving game result:', error);
                throw error;
            }
        },
 
        async updateDashboard() {
            try {
                const [leaderboardData, levelData, achievementsData, challengeData] = await Promise.all([
                    fetch('${API_BASE_URL}/leaderboard.php').then(r => r.json()),
                    fetch('${API_BASE_URL}/users/get_level_info.php').then(r => r.json()),
                    fetch('${API_BASE_URL}/achievements/get_user_achievements.php').then(r => r.json()),
                    fetch('${API_BASE_URL}/challenges/get_weekly_challenge.php').then(r => r.json())
                ]);
 
                if (document.getElementById('totalPlayers')) {
                    this.animateCounter('totalPlayers', leaderboardData.stats.total_players);
                    this.animateCounter('highestScore', leaderboardData.stats.highest_score);
                    this.animateCounter('averageScore', Math.round(leaderboardData.stats.average_score));
                }
 
                if (levelData.success && document.getElementById('playerLevelInfo')) {
                    this.updateLevelInfo(levelData.level_info);
                }
 
                if (achievementsData.success && document.getElementById('achievementsContainer')) {
                    this.updateAchievements(achievementsData.achievements);
                }
 
                if (challengeData.success && document.getElementById('challengeInfo')) {
                    this.updateWeeklyChallenge(challengeData.challenge);
                }
 
                if (leaderboardData.success && document.getElementById('leaderboardBody')) {
                    this.renderLeaderboard(leaderboardData.leaderboard);
                }
 
                if (leaderboardData.success && document.getElementById('scoreChart')) {
                    this.updateScoreChart(leaderboardData.leaderboard);
                }
            } catch (error) {
                console.error('Error updating dashboard:', error);
                throw error;
            }
        },
 
        animateCounter(elementId, targetValue) {
            const element = document.getElementById(elementId);
            if (!element) return;
 
            const duration = 1000;
            const start = parseInt(element.textContent) || 0;
            const increment = (targetValue - start) / (duration / 16);
            let current = start;
 
            const animate = () => {
                current += increment;
                if ((increment > 0 && current >= targetValue) || 
                    (increment < 0 && current <= targetValue)) {
                    element.textContent = Math.round(targetValue);
                } else {
                    element.textContent = Math.round(current);
                    requestAnimationFrame(animate);
                }
            };
 
            animate();
        },
 
        updateLevelInfo(levelInfo) {
            const container = document.getElementById('playerLevelInfo');
            container.innerHTML = `
                <div class="rank-badge" style="background: ${levelInfo.rank_color}">
                    ${levelInfo.rank_title}
                </div>
                <p>Level ${levelInfo.current_level}</p>
                <p>${levelInfo.xp_progress} / ${levelInfo.xp_needed} XP to next level</p>
            `;
            
            document.querySelector('.progress-fill').style.width = 
                `${levelInfo.progress_percentage}%`;
        },
 
        updateAchievements(achievements) {
            const container = document.getElementById('achievementsContainer');
            container.innerHTML = achievements.map(achievement => `
                <div class="badge-card ${achievement.unlocked ? '' : 'badge-locked'}">
                    <i class="badge-icon ${achievement.icon}"></i>
                    <h3>${achievement.title}</h3>
                    <p>${achievement.description}</p>
                </div>
            `).join('');
        },
 
        updateWeeklyChallenge(challenge) {
            const container = document.getElementById('challengeInfo');
            container.innerHTML = `
                <h3>${challenge.title}</h3>
                <p>${challenge.description}</p>
                <div class="challenge-timer" id="challengeTimer"></div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${(challenge.progress / challenge.target) * 100}%"></div>
                </div>
                <p>${challenge.progress}/${challenge.target} completed</p>
            `;
            this.updateChallengeTimer(challenge.end_date);
        },
 
        renderLeaderboard(leaderboard) {
            const tbody = document.getElementById('leaderboardBody');
            tbody.innerHTML = leaderboard.map((player, index) => `
                <tr>
                    <td>
                        ${index < 3 ? 
                            `<span class="rank-badge rank-${index + 1}">${index + 1}</span>` : 
                            index + 1}
                    </td>
                    <td>
                        <div class="player-status">
                            <span>${player.username}</span>
                            <span class="rank-badge" style="background: ${player.rank_color}">
                                ${player.rank_title}
                            </span>
                        </div>
                    </td>
                    <td>${player.steps}</td>
                    <td>${player.completion_time}s</td>
                    <td>
                        ${player.achievements.map(ach => `
                            <span class="achievement-badge" title="${ach.description}">
                                ${ach.icon} ${ach.title}
                            </span>
                        `).join('')}
                    </td>
                    <td>
                        ${player.streak > 0 ? `
                            <span class="streak-badge">
                                <i class="fas fa-fire streak-flame"></i>
                                ${player.streak}
                            </span>
                        ` : '-'}
                    </td>
                </tr>
            `).join('');
        },
 
        updateScoreChart(leaderboard) {
            const ctx = document.getElementById('scoreChart');
            if (!ctx) return;
 
            if (window.scoreChart) {
                window.scoreChart.destroy();
            }
 
            window.scoreChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: leaderboard.map(player => player.username),
                    datasets: [{
                        label: 'Steps to Complete',
                        data: leaderboard.map(player => player.steps),
                        backgroundColor: leaderboard.map((_, index) => {
                            if (index === 0) return 'rgba(241, 196, 15, 0.8)';
                            if (index === 1) return 'rgba(189, 195, 199, 0.8)';
                            if (index === 2) return 'rgba(205, 127, 50, 0.8)';
                            return 'rgba(52, 152, 219, 0.6)';
                        }),
                        borderColor: leaderboard.map((_, index) => {
                            if (index === 0) return 'rgba(241, 196, 15, 1)';
                            if (index === 1) return 'rgba(189, 195, 199, 1)';
                            if (index === 2) return 'rgba(205, 127, 50, 1)';
                            return 'rgba(52, 152, 219, 1)';
                        }),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Steps',
                                font: { weight: 'bold' }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        title: {
                            display: true,
                            text: 'Player Performance',
                            font: { size: 16, weight: 'bold' }
                        }
                    }
                }
            });
        }
    },
 
    startNewGame() {
        return {
            steps: 0,
            startTime: Date.now(),
            isComplete: false,
            level: 'medium' // default level
        };
    },
 
    finishGame(gameState) {
        const endTime = Date.now();
        const result = {
            steps: gameState.steps,
            timeTaken: Math.floor((endTime - gameState.startTime) / 1000),
            level: gameState.level
        };
 
        this.gameProgress.saveGameResult(result);
        return result;
    }
 };
 
 // Fungsi untuk menampilkan notifikasi challenge
 function showChallengeNotification(challenge, xpEarned) {
    const notification = document.createElement('div');
    notification.className = 'game-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-trophy"></i>
            <div class="notification-text">
                <h4>Challenge Completed!</h4>
                <p>${challenge.title}</p>
                <p class="xp-earned">+${xpEarned} XP</p>
            </div>
            <button class="notification-close">×</button>
        </div>
    `;
 
    document.body.appendChild(notification);
 
    // Auto hide after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
 
    // Close button handler
    notification.querySelector('.notification-close').onclick = () => {
        notification.remove();
    };
 }
 
 // Tambahkan CSS untuk notifikasi
 const style = document.createElement('style');
 style.textContent = `
    .game-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    }
 
    .notification-content {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        gap: 15px;
    }
 
    .notification-content i {
        font-size: 24px;
        color: #ffd700;
    }
 
    .notification-text {
        flex: 1;
    }
 
    .notification-text h4 {
        margin: 0;
        color: #2b2d42;
    }
 
    .notification-text p {
        margin: 5px 0 0;
        color: #64748b;
    }
 
    .xp-earned {
        color: #4361ee !important;
        font-weight: 500;
    }
 
    .notification-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #64748b;
        cursor: pointer;
        padding: 0 5px;
    }
 
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
 `;
 
 document.head.appendChild(style);
 
 export default gameData;