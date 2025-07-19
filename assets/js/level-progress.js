// Level progress functionality
const API_BASE_URL = 'https://games.teluapp.org/8puzzle/backend/api';
class LevelProgress {
    constructor() {
        this.progressBar = document.getElementById('levelProgress');
        this.levelDisplay = document.getElementById('levelDisplay');
        this.xpInfo = document.getElementById('xpInfo');
    }

    // Calculate XP needed for next level
    getXpForLevel(level) {
        return Math.floor(1000 * Math.pow(1.5, level - 1));
    }

    // Update progress display
    updateProgress(currentXp, currentLevel) {
        const nextLevelXp = this.getXpForLevel(currentLevel + 1);
        const currentLevelXp = this.getXpForLevel(currentLevel);
        const xpProgress = currentXp - currentLevelXp;
        const xpNeeded = nextLevelXp - currentLevelXp;
        
        // Update progress bar
        const progressPercentage = (xpProgress / xpNeeded) * 100;
        if (this.progressBar) {
            this.progressBar.style.width = `${progressPercentage}%`;
        }

        // Update level display
        if (this.levelDisplay) {
            this.levelDisplay.textContent = `Level ${currentLevel}`;
        }

        // Update XP info
        if (this.xpInfo) {
            this.xpInfo.textContent = `${xpProgress}/${xpNeeded} XP to next level`;
        }

        return {
            progress: progressPercentage,
            xpProgress,
            xpNeeded
        };
    }

    // Show level up animation
    async showLevelUpAnimation(oldLevel, newLevel) {
        if (oldLevel >= newLevel) return;

        const levelUpOverlay = document.createElement('div');
        levelUpOverlay.className = 'level-up-overlay';
        levelUpOverlay.innerHTML = `
            <div class="level-up-content">
                <h2>Level Up!</h2>
                <p>You've reached level ${newLevel}</p>
                <div class="rewards">
                    <p>Rewards:</p>
                    <ul>
                        <li>New achievements unlocked</li>
                        <li>Bonus XP multiplier increased</li>
                    </ul>
                </div>
            </div>
        `;

        document.body.appendChild(levelUpOverlay);
        
        // Add animation classes
        setTimeout(() => {
            levelUpOverlay.classList.add('show');
        }, 100);

        // Remove overlay after animation
        setTimeout(() => {
            levelUpOverlay.classList.remove('show');
            setTimeout(() => {
                levelUpOverlay.remove();
            }, 500);
        }, 3000);
    }

    // Update progress after completing a puzzle
    async updateAfterPuzzle(steps, completionTime) {
        try {
            const response = await fetch('${API_BASE_URL}/users/update_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: getCurrentUserId(), // Implement this function
                    steps: steps,
                    completion_time: completionTime
                })
            });

            const data = await response.json();
            
            if (data.success) {
                const oldLevel = parseInt(this.levelDisplay.textContent.split(' ')[1]);
                const newLevel = data.progress.new_level;

                // Update progress display
                this.updateProgress(data.progress.new_total_xp, newLevel);

                // Show level up animation if applicable
                if (newLevel > oldLevel) {
                    await this.showLevelUpAnimation(oldLevel, newLevel);
                }

                // Show XP earned notification
                this.showXpEarnedNotification(data.progress.xp_earned);

                // Update achievements if any
                if (data.progress.achievements_earned?.length > 0) {
                    this.showNewAchievements(data.progress.achievements_earned);
                }
            }

            return data;

        } catch (error) {
            console.error('Error updating progress:', error);
            throw error;
        }
    }

    // Show XP earned notification
    showXpEarnedNotification(xpEarned) {
        const notification = document.createElement('div');
        notification.className = 'xp-notification';
        notification.textContent = `+${xpEarned} XP`;

        document.body.appendChild(notification);

        // Animate and remove
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 2000);
    }

    // Show new achievements
    showNewAchievements(achievements) {
        achievements.forEach(achievement => {
            const notification = document.createElement('div');
            notification.className = 'achievement-notification';
            notification.innerHTML = `
                <div class="achievement-icon">🏆</div>
                <div class="achievement-info">
                    <h3>New Achievement!</h3>
                    <p>${achievement}</p>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 3000);
        });
    }
}

// CSS for animations
const style = document.createElement('style');
style.textContent = `
    .level-up-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1000;
    }

    .level-up-overlay.show {
        opacity: 1;
    }

    .level-up-content {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        text-align: center;
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }

    .level-up-overlay.show .level-up-content {
        transform: translateY(0);
    }

    .xp-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #4361ee;
        color: white;
        padding: 1rem;
        border-radius: 8px;
        opacity: 0;
        transform: translateX(20px);
        transition: all 0.3s ease;
    }

    .xp-notification.show {
        opacity: 1;
        transform: translateX(0);
    }

    .achievement-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        padding: 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        opacity: 0;
        transform: translateX(20px);
        transition: all 0.3s ease;
    }

    .achievement-notification.show {
        opacity: 1;
        transform: translateX(0);
    }

    .achievement-icon {
        font-size: 2rem;
    }
`;
document.head.appendChild(style);

// Export class
export default LevelProgress;