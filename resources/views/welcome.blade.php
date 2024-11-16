<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>TARAHUM</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            body {
              margin: 0;
              height: 100vh;
              display: flex;
              justify-content: center;
              align-items: center;
              background-color: #0f172a;
              font-family: 'Arial', sans-serif;
              overflow: hidden;
            }
        
            .hero {
              text-align: center;
            }
        
            .letter-container {
              display: inline-block;
              height: 100px;
              overflow: hidden;
              margin: 0 2px;
            }
        
            .letter-slot {
              display: inline-flex;
              flex-direction: column;
              font-size: 6rem;
              font-weight: bold;
              color: #fff;
              transform-style: preserve-3d;
              transition: transform 0.3s;
            }
        
            .letter {
              height: 100px;
              width: 60px;
              display: flex;
              justify-content: center;
              align-items: center;
              backface-visibility: hidden;
            }
        
            @keyframes slideDown {
              0% { transform: translateY(-100%); }
              100% { transform: translateY(0); }
            }
        
            @keyframes slideUp {
              0% { transform: translateY(100%); }
              100% { transform: translateY(0); }
            }
        </style>
    </head>
    <body class="antialiased">
        
      <div class="hero">
        <div id="text"></div>
      </div>

        <script>
            const finalText = "TARAHUM";
            const container = document.getElementById("text");
            const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const animationDuration = 2000; // 2 seconds total
            
            finalText.split('').forEach((targetLetter, index) => {
              const letterContainer = document.createElement('div');
              letterContainer.className = 'letter-container';
              const letterSlot = document.createElement('div');
              letterSlot.className = 'letter-slot';
              letterContainer.appendChild(letterSlot);
              container.appendChild(letterContainer);
        
              // Calculate number of iterations based on position
              const iterations = 10 + Math.floor(Math.random() * 5);
              const iterationDuration = animationDuration / iterations;
              
              // Alternate direction based on index
              const isEven = index % 2 === 0;
              
              let currentIteration = 0;
              
              function updateLetter() {
                if (currentIteration >= iterations) {
                  letterSlot.textContent = targetLetter;
                  letterSlot.style.animation = `${isEven ? 'slideDown' : 'slideUp'} 0.3s ease-out`;
                  return;
                }
                
                const randomLetter = letters[Math.floor(Math.random() * letters.length)];
                letterSlot.textContent = randomLetter;
                letterSlot.style.animation = `${isEven ? 'slideDown' : 'slideUp'} 0.3s ease-out`;
                
                currentIteration++;
                setTimeout(updateLetter, iterationDuration);
              }
              
              // Start the animation with a staggered delay
              setTimeout(() => {
                updateLetter();
              }, index * 100);
            });
        </script>
    </body>
</html>
