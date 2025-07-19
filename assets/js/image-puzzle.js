// Generator Puzzle dengan LCG untuk pengacakan tervalidasi
// const API_BASE_URL = 'https://games.teluapp.org/8puzzle/backend/api';

class PuzzleLevel {
    constructor(seed) {
        this.seed = seed || Date.now();
        this.a = 1664525;         
        this.c = 1013904223;      
        this.m = Math.pow(2, 32); 
    }

    next() {
        this.seed = (this.a * this.seed + this.c) % this.m;
        return this.seed;
    }

    shuffleByLevel(pieces, level) {
        const shuffleCount = this.getShuffleCountByLevel(level);
        
        for (let i = 0; i < shuffleCount; i++) {
            const j = this.next() % pieces.length;
            const k = this.next() % pieces.length;
            [pieces[j], pieces[k]] = [pieces[k], pieces[j]];
        }
        
        return this.ensureSolvable(pieces) ? pieces : this.makeScrambleSolvable(pieces);
    }

    getShuffleCountByLevel(level) {
        switch(level) {
            case 'easy': return 20;    
            case 'medium': return 40;  
            case 'hard': return 60;    
            default: return 30;
        }
    }

    ensureSolvable(pieces) {
        let inversions = 0;
        for (let i = 0; i < pieces.length - 1; i++) {
            for (let j = i + 1; j < pieces.length; j++) {
                if (pieces[i] > pieces[j]) inversions++;
            }
        }
        return inversions % 2 === 0;
    }

    makeScrambleSolvable(pieces) {
        if (pieces.length >= 2) {
            [pieces[0], pieces[1]] = [pieces[1], pieces[0]];
        }
        return pieces;
    }
}

var timerFunction;

var imagePuzzle = {
    stepCount: 0,
    startTime: new Date().getTime(),
    puzzleLevel: null,
    currentLevel: 'medium',
    
    // Cek autentikasi sebelum memulai permainan
  checkAuth: async function() {
    try {
        // Make sure Auth is defined before using it
        if (typeof Auth === 'undefined') {
            console.error('Auth object is not defined');
            alert('Authentication module not loaded properly');
            return false;
        }
        
        const isAuthenticated = await Auth.checkAuth();
        if (!isAuthenticated) {
            Auth.redirectToLogin();
            return false;
        }
        return true;
    } catch (error) {
        console.error('Authentication check failed:', error);
        return false;
    }
},
    startGame: function (images, level = 'medium') {
        // Cek autentikasi sebelum memulai permainan
        if (!this.checkAuth()) return;
        
        this.currentLevel = level;
        const gridSize = this.getGridSizeByLevel(level);
        this.puzzleLevel = new PuzzleLevel(Date.now());
        this.setImage(images, gridSize);
        helper.doc('playPanel').style.display = 'block';
        this.stepCount = 0;
        this.startTime = new Date().getTime();
        this.tick();
    },

    getGridSizeByLevel: function(level) {
        switch(level) {
            case 'easy': return 3;
            case 'medium': return 4;
            case 'hard': return 5;
            default: return 4;
        }
    },

    tick: function () {
        var now = new Date().getTime();
        var elapsedTime = parseInt((now - imagePuzzle.startTime) / 1000, 10);
        helper.doc('timerPanel').textContent = elapsedTime;
        timerFunction = setTimeout(imagePuzzle.tick, 1000);
    },

    setImage: function (images, gridSize) {
        var percentage = 100 / (gridSize - 1);
        var image = images[Math.floor(Math.random() * images.length)];
        helper.doc('imgTitle').innerHTML = image.title;
        helper.doc('actualImage').setAttribute('src', image.src);
        helper.doc('sortable').innerHTML = '';

        const pieces = Array.from({length: gridSize * gridSize}, (_, i) => i);
        const shuffledPieces = this.puzzleLevel.shuffleByLevel(pieces, this.currentLevel);

        shuffledPieces.forEach((pieceIndex, position) => {
            var xpos = (percentage * (pieceIndex % gridSize)) + '%';
            var ypos = (percentage * Math.floor(pieceIndex / gridSize)) + '%';

            let li = document.createElement('li');
            li.id = pieceIndex;
            li.setAttribute('data-value', pieceIndex);
            li.style.backgroundImage = 'url(' + image.src + ')';
            li.style.backgroundSize = (gridSize * 100) + '%';
            li.style.backgroundPosition = xpos + ' ' + ypos;
            li.style.width = 400 / gridSize + 'px';
            li.style.height = 400 / gridSize + 'px';

            li.setAttribute('draggable', 'true');
            li.ondragstart = (event) => event.dataTransfer.setData('data', event.target.id);
            li.ondragover = (event) => event.preventDefault();
            li.ondrop = (event) => {
                let origin = helper.doc(event.dataTransfer.getData('data'));
                let dest = helper.doc(event.target.id);
                let p = dest.parentNode;

                if (origin && dest && p) {
                    let temp = dest.nextSibling;
                    let x_diff = origin.offsetLeft-dest.offsetLeft;
                    let y_diff = origin.offsetTop-dest.offsetTop;

                    if(y_diff == 0 && x_diff >0){
                        p.insertBefore(origin, dest);
                        p.insertBefore(temp, origin);
                    } else {
                        p.insertBefore(dest, origin);
                        p.insertBefore(origin, temp);
                    }

                    let vals = Array.from(helper.doc('sortable').children).map(x => x.id);
                    var now = new Date().getTime();
                    helper.doc('stepCount').textContent = ++imagePuzzle.stepCount;
                    document.querySelector('.timeCount').textContent = (parseInt((now - imagePuzzle.startTime) / 1000, 10));

                    if (this.isSolved(vals)) {
                        // Simpan skor ketika puzzle selesai
                        const timeTaken = parseInt((now - imagePuzzle.startTime) / 1000, 10);
                        this.saveScore({
                            level: this.currentLevel,
                            steps: this.stepCount,
                            time: timeTaken
                        });
                    }
                }
            };
            helper.doc('sortable').appendChild(li);
        });
    },

    isSolved: function(arrangement) {
        return arrangement.every((elem, index) => parseInt(elem) === index);
    },

    saveScore: async function(scoreData) {
        try {
                const response = await fetch(`${API_BASE_URL}/scores/save_score.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    steps: scoreData.steps,
                    completion_time: scoreData.time,
                    difficulty: scoreData.level,  // Changed from level to difficulty
                    xp_earned: this.calculateXP(scoreData)
                }),
                credentials: 'include'
            });

            if (!response.ok) throw new Error(await response.text());
            
            const result = await response.json();
            if (result.success) {
                const gameOverMessage = `
                    <div class="success-message">
                        <h2>Permainan Selesai!</h2>
                        <p>Selamat!!</p>
                        <div class="stats">
                            <p>Langkah: ${scoreData.steps} langkah</p>
                            <p>Waktu: ${scoreData.time} detik</p>
                            <p>Level: ${scoreData.level}</p>
                            <p>XP Earned: +${this.calculateXP(scoreData)} XP</p>
                        </div>
                        <div class="button-group">
                            <button onclick="window.location.reload(true);">Main Lagi</button>
                            <button onclick="window.location.href='dashboard.html'">Papan Peringkat</button>
                        </div>
                    </div>
                `;
                helper.doc('actualImageBox').innerHTML = gameOverMessage;
                if (timerFunction) clearTimeout(timerFunction);
            }
        } catch (error) {
            console.error('Save score error:', error);
            alert('Gagal menyimpan skor: ' + error.message);
        }
    },
    
    // Tambahkan method untuk menghitung XP
    calculateXP: function(scoreData) {
        let baseXP = 100; // XP dasar
    
        // Bonus berdasarkan level
        const levelMultiplier = {
            'easy': 1,
            'medium': 1.5,
            'hard': 2
        };
    
        // Bonus berdasarkan waktu
        let timeBonus = Math.max(0, 300 - scoreData.time) * 0.5;
    
        // Bonus berdasarkan langkah
        let stepBonus = Math.max(0, 50 - scoreData.steps) * 2;
    
        return Math.round(baseXP * levelMultiplier[scoreData.level] + timeBonus + stepBonus);
    }
};

var helper = {
    doc: (id) => document.getElementById(id) || document.createElement("div")
};

window.imagePuzzle = imagePuzzle;


