// Constants
//const API_BASE_URL = 'https://games.teluapp.org/8puzzle/backend/api';
// Initialize profile page
document.addEventListener('DOMContentLoaded', initializeProfile);

function updateHeaders() {
    // Tambahkan CSS untuk header styling
    const style = document.createElement('style');
    style.textContent = `
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.5rem;
        }

        .header-icon {
            color: #4361ee;
            font-size: 1.3rem;
        }
    `;
    document.head.appendChild(style);

    const headers = [
        {
            selector: '.level-card h2',
            icon: 'fa-bolt',
            text: 'Level Progress'
        },
        {
            selector: '.activity-card h2',
            icon: 'fa-chart-bar',
            text: 'Daily Activity'
        },
        {
            selector: '.stats-summary-card h2',
            icon: 'fa-chart-line',
            text: 'Performance Summary'
        },
        {
            selector: '.chart-card h2',
            icon: 'fa-chart-area',
            text: 'Performance History'
        },
        {
            selector: '.achievements-section h2',
            icon: 'fa-trophy',
            text: 'Achievements'
        },
        {
            selector: '.history-card h2',
            icon: 'fa-gamepad',
            text: 'Recent Games'
        }
    ];

    headers.forEach(header => {
        const element = document.querySelector(header.selector);
        if (element) {
            element.className = 'section-header';
            element.innerHTML = `
                <i class="fas ${header.icon} header-icon"></i>
                <span>${header.text}</span>
            `;
        }
    });
}

window.recentGames = [];

// Update fungsi initializeProfile untuk menyimpan games
async function initializeProfile() {
    try {
        console.log('Fetching profile data...');
        const response = await fetch(`${API_BASE_URL}/users/get_profile.php`, {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success) {
            // Simpan games di variable global
            window.recentGames = data.recent_games;
            
            updateProfileInfo(data.player);
            updateLevelProgress(data.player);
            addActivityTracker(data.recent_games);
            addStatsSummary(data.player, data.recent_games);
            updateAchievements(data.achievements || []);
            updateGameHistory(data.recent_games); // Initial history
            initializePerformanceChart(data.recent_games);
            updateHeaders();
        } else {
            console.error('Failed to load profile:', data.error);
            if (data.error === 'Not authenticated') {
                window.location.href = 'login.html';
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

// Update event listener untuk history filter
document.addEventListener('DOMContentLoaded', () => {
    const filterSelect = document.getElementById('historyFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function(e) {
            const filterType = e.target.value;
            let filteredGames = [...window.recentGames]; // Gunakan games dari variable global

            switch(filterType) {
                case 'best':
                    filteredGames.sort((a, b) => a.steps - b.steps);
                    filteredGames = filteredGames.slice(0, 10);
                    break;
                case 'recent':
                    filteredGames = [...window.recentGames].slice(0, 10); // Get 10 most recent
                    break;
                case 'all':
                default:
                    filteredGames = [...window.recentGames]; // Show all games
                    break;
            }

            updateGameHistory(filteredGames);
        });
    }
});


function updateProfileInfo(player) {
    const username = player.username || 'User';
    const initials = username.split(' ')[0].charAt(0).toUpperCase() + 
                    (username.split(' ')[1] ? username.split(' ')[1].charAt(0).toUpperCase() : '');
    
    document.getElementById('avatarInitials').textContent = initials;
    document.getElementById('playerName').textContent = username;
    
    const rankBadge = document.getElementById('playerRank');
    rankBadge.style.backgroundColor = player.rank_color || '#95a5a6';
    rankBadge.textContent = player.rank_title || 'Beginner';

    document.getElementById('totalGames').textContent = player.games_played || '0';
    document.getElementById('bestScore').textContent = player.best_score || '0';
    document.getElementById('currentStreak').textContent = player.current_streak || '0';
    
    const winRate = player.games_played > 0 ? 
        ((player.games_won / player.games_played) * 100).toFixed(1) : '0.0';
    document.getElementById('winRate').textContent = `${winRate}%`;
}

function updateLevelProgress(player) {
    // Data level sudah dihitung di backend
    const level = player.level;
    const currentXP = player.xp_progress.current;
    const neededXP = player.xp_progress.needed;
    
    document.getElementById('levelDisplay').textContent = `Level ${level}`;
    
    const progressPercentage = (currentXP / neededXP) * 100;
    const progressBar = document.getElementById('levelProgress');
    progressBar.style.width = `${Math.min(100, Math.max(0, progressPercentage))}%`;
    
    document.getElementById('xpInfo').textContent = 
        `${currentXP}/${neededXP} XP to next level`;
}

function addActivityTracker(games) {
    const container = document.getElementById('activityTracker');
    if (!container || !games.length) return;

    // Add view selector
    const filterContainer = document.createElement('div');
    filterContainer.className = 'activity-filter';
    const viewSelect = document.createElement('select');
    viewSelect.id = 'activityView';
    
    // Get months for monthly view
    const months = [...new Set(games.map(game => {
        const date = new Date(game.created_at);
        return `${date.getFullYear()}-${date.getMonth() + 1}`;
    }))].sort().reverse();

    // Add options
    viewSelect.innerHTML = `
        <option value="period">Active Period (14 days)</option>
        ${months.map(month => {
            const [year, monthNum] = month.split('-');
            const monthName = new Date(year, monthNum - 1).toLocaleString('default', { month: 'long' });
            return `<option value="${month}">${monthName} ${year}</option>`;
        }).join('')}
    `;

    filterContainer.appendChild(viewSelect);
    container.parentElement.insertBefore(filterContainer, container);

    function showActivePeriod() {
        // Sort games by date
        const sortedGames = [...games].sort((a, b) => 
            new Date(a.created_at) - new Date(b.created_at)
        );

        const today = new Date();
        let startDate;
        let foundActivePeriod = false;

        // Find active period
        for (let i = 0; i < sortedGames.length; i++) {
            const gameDate = new Date(sortedGames[i].created_at);
            const periodEnd = new Date(gameDate);
            periodEnd.setDate(gameDate.getDate() + 13);

            if (periodEnd >= today && !foundActivePeriod) {
                startDate = gameDate;
                foundActivePeriod = true;
                break;
            }
        }

        // If no active period, use last game
        if (!foundActivePeriod && sortedGames.length > 0) {
            startDate = new Date(sortedGames[sortedGames.length - 1].created_at);
        }

        if (!startDate) return;

        // Set end date (14 days from start)
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 13);

        displayActivityData(startDate, endDate);
    }

    function showMonthlyView(monthYear) {
        const [year, month] = monthYear.split('-');
        const startDate = new Date(year, month - 1, 1);
        const endDate = new Date(year, month, 0);
        displayActivityData(startDate, endDate);
    }

    function displayActivityData(startDate, endDate) {
        const days = [];
        const dailyGames = new Map();
    
        // Generate days
        let currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            const dateStr = currentDate.toDateString();
            dailyGames.set(dateStr, 0);
            days.push({
                date: new Date(currentDate),
                count: 0
            });
            currentDate.setDate(currentDate.getDate() + 1);
        }
    
        // Count games per day
        games.forEach(game => {
            const gameDate = new Date(game.created_at);
            if (gameDate >= startDate && gameDate <= endDate) {
                const gameDateStr = gameDate.toDateString();
                if (dailyGames.has(gameDateStr)) {
                    dailyGames.set(gameDateStr, dailyGames.get(gameDateStr) + 1);
                }
            }
        });
    
        // Update days with counts
        days.forEach(day => {
            day.count = dailyGames.get(day.date.toDateString()) || 0;
        });
    
        // Find max count for scaling
        const maxCount = Math.max(...days.map(day => day.count), 1);
    
        // Generate HTML
        container.innerHTML = days.map(day => {
            let activityClass = 'low';
            if (day.count > 10) {
                activityClass = 'high';
            } else if (day.count > 5) {
                activityClass = 'medium';
            }
    
            return `
                <div class="day-block">
                    <div class="day-label">${day.date.toLocaleDateString('en-US', { 
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric'
                    })}</div>
                    <div class="activity-bar">
                        ${day.count > 0 ? `
                            <div class="activity-fill ${activityClass}"></div>
                        ` : ''}
                    </div>
                    <div class="activity-count">${day.count} games</div>
                </div>
            `;
        }).join('');
    }

    // Event listener for view changes
    viewSelect.addEventListener('change', (e) => {
        if (e.target.value === 'period') {
            showActivePeriod();
        } else {
            showMonthlyView(e.target.value);
        }
    });

    // Initial display
    showActivePeriod();
}

function addStatsSummary(player, games) {
    const container = document.getElementById('statsSummary');
    if (!container) return;

    // Calculate average moves
    const avgMoves = games.length > 0 
        ? games.reduce((sum, game) => sum + game.steps, 0) / games.length 
        : 0;

    // Calculate completion rate
    const completionRate = player.games_played > 0
        ? ((player.games_won / player.games_played) * 100).toFixed(1)
        : 0;

    // Calculate average time
    const validTimes = games.filter(game => game.completion_time > 0);
    const avgTime = validTimes.length > 0
        ? validTimes.reduce((sum, game) => sum + game.completion_time, 0) / validTimes.length
        : 0;

    const stats = [
        {
            icon: '🎯',
            value: avgMoves.toFixed(1),
            label: 'Avg. Moves',
            trend: 'Improving'
        },
        {
            icon: '⚡',
            value: avgTime.toFixed(1),
            label: 'Avg. Time (s)',
            trend: 'Stable'
        },
        {
            icon: '🏆',
            value: `${completionRate}%`,
            label: 'Success Rate',
            trend: 'Trending Up'
        },
        {
            icon: '🔥',
            value: player.current_streak,
            label: 'Current Streak',
            trend: 'Best Ever'
        }
    ];

    container.innerHTML = stats.map(stat => `
        <div class="stat-block">
            <div class="stat-icon">${stat.icon}</div>
            <div class="stat-value">${stat.value}</div>
            <div class="stat-label">${stat.label}</div>
            <div class="stat-trend">
                <i class="fas fa-arrow-up trend-up"></i>
                <span>${stat.trend}</span>
            </div>
        </div>
    `).join('');
}

function getAchievementContent(title) {
    // Menggunakan emoji atau karakter khusus sebagai icon
    const icons = {
        'First Victory': '🏆',
        'Speed Demon': '⚡',
        'Perfect Solver': '🎯',
        'Dedicated Player': '⭐',
        'Streak Master': '🔥',
        'Pro Player': '👑'
    };
    return icons[title] || '🎮';
}

function updateAchievements(achievements) {
    const container = document.getElementById('achievementsContainer');
    if (!container) return;

    container.innerHTML = achievements.map(achievement => {
        const progressPercent = Math.min(100, (achievement.progress / achievement.target) * 100);
        const iconClass = getAchievementIconClass(achievement.title);
        
        return `
            <div class="achievement-card ${achievement.unlocked ? 'unlocked' : 'locked'}">
                <div class="achievement-header">
                    <div class="achievement-icon ${iconClass}">
                        ${achievement.icon}
                    </div>
                    <div class="achievement-status">
                        ${achievement.unlocked ? 
                            '<span class="status-unlocked">Unlocked</span>' : 
                            '<span class="status-locked">Locked</span>'
                        }
                    </div>
                </div>
                <div class="achievement-info">
                    <h3>${achievement.title}</h3>
                    <p>${achievement.description}</p>
                    <div class="achievement-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progressPercent}%"></div>
                        </div>
                        <span class="progress-text">${achievement.progress}/${achievement.target}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getAchievementIconClass(title) {
    const classMap = {
        'First Victory': 'first-victory',
        'Speed Demon': 'speed-demon',
        'Perfect Solver': 'perfect-solver',
        'Dedicated Player': 'dedicated-player',
        'Streak Master': 'streak-master',
        'Pro Player': 'pro-player'
    };
    return classMap[title] || '';
}

function updateGameHistory(games) {
    const tableBody = document.getElementById('gameHistoryTable');
    if (!tableBody) return;

    const html = games.map(game => {
        const date = new Date(game.created_at).toLocaleString();
        return `
            <tr class="table-row">
                <td>${date}</td>
                <td>${game.difficulty || '-'}</td>
                <td>${game.steps}</td>
                <td>${game.completion_time || '-'}s</td>
                <td>${game.xp_earned} XP</td>
            </tr>
        `;
    }).join('');

    tableBody.innerHTML = html;
}

function getGameAchievementIcons(game) {
    const icons = [];
    if (game.steps <= 10) icons.push('🎯');
    if (game.completion_time <= 30) icons.push('⚡');
    if (game.difficulty === 'hard') icons.push('🔥');
    return icons.join(' ') || '-';
}

function initializePerformanceChart(games) {
    const ctx = document.getElementById('performanceChart');
    if (!ctx) return;

    // Add filter controls
    const filterContainer = document.createElement('div');
    filterContainer.className = 'chart-filters';
    filterContainer.innerHTML = `
        <div class="filter-group">
            <select id="timeRangeFilter" class="chart-filter">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="all">All Time</option>
            </select>
            <select id="dataTypeFilter" class="chart-filter">
                <option value="both">Steps & Time</option>
                <option value="steps">Steps Only</option>
                <option value="time">Time Only</option>
            </select>
        </div>
    `;
    ctx.parentElement.insertBefore(filterContainer, ctx);

    // Filter and prepare data
    function prepareChartData(timeRange, dataType) {
        let filteredGames = [...games].filter(game => game.steps > 0);

        // Apply time range filter
        if (timeRange !== 'all') {
            const daysAgo = new Date();
            daysAgo.setDate(daysAgo.getDate() - parseInt(timeRange));
            filteredGames = filteredGames.filter(game => 
                new Date(game.created_at) >= daysAgo
            );
        }

        // Sort by date
        filteredGames = filteredGames
            .map(game => ({
                date: new Date(game.created_at).toLocaleDateString(),
                steps: game.steps,
                time: game.completion_time || null
            }))
            .reverse();

        // Prepare datasets based on data type
        const datasets = [];
        if (dataType === 'both' || dataType === 'steps') {
            datasets.push({
                label: 'Steps',
                data: filteredGames.map(game => game.steps),
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            });
        }
        if (dataType === 'both' || dataType === 'time') {
            datasets.push({
                label: 'Completion Time (seconds)',
                data: filteredGames.map(game => game.time),
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                tension: 0.4,
                fill: true,
                hidden: dataType === 'both',
                pointRadius: 4,
                pointHoverRadius: 6
            });
        }

        return {
            labels: filteredGames.map(game => game.date),
            datasets
        };
    }

    // Create or update chart
    let chart = null;
    
    function updateChart(timeRange, dataType) {
        const chartData = prepareChartData(timeRange, dataType);

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        padding: 12,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            padding: 10,
                            color: '#666'
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            padding: 10,
                            color: '#666',
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        right: 20,
                        bottom: 20,
                        left: 20
                    }
                }
            }
        });
    }
    
    // Add event listeners to filters
    const timeRangeFilter = document.getElementById('timeRangeFilter');
    const dataTypeFilter = document.getElementById('dataTypeFilter');

    timeRangeFilter.addEventListener('change', () => 
        updateChart(timeRangeFilter.value, dataTypeFilter.value)
    );
    dataTypeFilter.addEventListener('change', () => 
        updateChart(timeRangeFilter.value, dataTypeFilter.value)
    );

    // Initial chart render
    updateChart('7', 'both');
}

    const filterSelect = document.getElementById('historyFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function(e) {
            const filterType = e.target.value;
            let filteredGames = [...games];

            switch(filterType) {
                case 'best':
                    filteredGames.sort((a, b) => a.steps - b.steps);
                    filteredGames = filteredGames.slice(0, 10);
                    break;
                case 'recent':
                    filteredGames = filteredGames.slice(0, 10);
                    break;
            }

            updateGameHistory(filteredGames);
        });
    }
