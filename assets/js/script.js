/**
 * Rental PS Booking System - Front-end JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. FAQ Accordion Logic
    const faqQuestions = document.querySelectorAll('.faq-question');
    if (faqQuestions.length > 0) {
        faqQuestions.forEach(q => {
            q.addEventListener('click', () => {
                const item = q.parentElement;
                item.classList.toggle('active');
            });
        });
    }

    // 2. Booking Steps Wizard Control
    const bookingForm = document.getElementById('booking-wizard-form');
    if (bookingForm) {
        let currentStep = 1;
        const totalSteps = 4; // Step 1-4 are in booking.php, Step 5 is payment.php
        
        const stepItems = document.querySelectorAll('.step-item');
        const panels = document.querySelectorAll('.booking-panel');
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        
        // Console selection variables
        const consoleCards = document.querySelectorAll('.console-card');
        const inputConsoleId = document.getElementById('selected_console_id');
        
        // Room selection variables
        const roomCards = document.querySelectorAll('.room-card');
        const inputRoomId = document.getElementById('selected_room_id');
        const inputPeopleCount = document.getElementById('people_count');
        const selectGame = document.getElementById('game_id');
        
        // Date & Time variables
        const inputDate = document.getElementById('booking_date');
        const inputStartTime = document.getElementById('start_time');
        const inputDuration = document.getElementById('duration');
        
        // Set minimum date to today
        if (inputDate) {
            const today = new Date().toISOString().split('T')[0];
            inputDate.setAttribute('min', today);
        }

        // Initialize display
        updateStepUI();

        // Console Cards Selector
        consoleCards.forEach(card => {
            card.addEventListener('click', () => {
                consoleCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                const consoleId = card.dataset.consoleId;
                inputConsoleId.value = consoleId;
                
                // Fetch games for this console
                fetchGames(consoleId);
                
                // Update live summary
                updateSummaryText('summary-console', card.dataset.consoleName);
                
                // Reset game selection in UI
                updateSummaryText('summary-game', '-');
                if(selectGame) selectGame.value = '';
                
                // Save selection to session
                saveSessionData({ console_id: consoleId });
            });
        });

        // Room Cards Selector
        roomCards.forEach(card => {
            card.addEventListener('click', () => {
                roomCards.forEach(r => r.classList.remove('selected'));
                card.classList.add('selected');
                const roomId = card.dataset.roomId;
                inputRoomId.value = roomId;
                
                // Max people validation
                const maxPeople = parseInt(card.dataset.maxPeople);
                inputPeopleCount.setAttribute('max', maxPeople);
                if (parseInt(inputPeopleCount.value) > maxPeople) {
                    inputPeopleCount.value = maxPeople;
                }
                
                // Update live summary
                updateSummaryText('summary-room', `${card.dataset.roomName} (${formatRupiah(card.dataset.roomPrice)}/jam)`);
                
                // Recalculate cost
                recalculateTotal();
                
                // Save selection to session
                saveSessionData({ 
                    room_id: roomId,
                    people_count: inputPeopleCount.value 
                });
            });
        });

        if (inputPeopleCount) {
            inputPeopleCount.addEventListener('change', () => {
                const selectedRoomCard = document.querySelector('.room-card.selected');
                if (selectedRoomCard) {
                    const max = parseInt(selectedRoomCard.dataset.maxPeople);
                    const val = parseInt(inputPeopleCount.value);
                    if (val > max) {
                        alert(`Kapasitas maksimal ruangan ini adalah ${max} orang.`);
                        inputPeopleCount.value = max;
                    }
                    if (val < 1) inputPeopleCount.value = 1;
                }
                saveSessionData({ people_count: inputPeopleCount.value });
            });
        }

        if (selectGame) {
            selectGame.addEventListener('change', () => {
                const selectedOption = selectGame.options[selectGame.selectedIndex];
                const gameName = selectedOption.text || '-';
                updateSummaryText('summary-game', gameName);
                saveSessionData({ game_id: selectGame.value });
            });
        }

        // Date & Time triggers
        [inputDate, inputStartTime, inputDuration].forEach(element => {
            if (element) {
                element.addEventListener('change', () => {
                    if (inputDate.value && inputStartTime.value && inputDuration.value) {
                        // Check availability via AJAX
                        checkAvailability();
                    }
                    recalculateTotal();
                });
            }
        });

        // Navigation button events
        if (btnNext) {
            btnNext.addEventListener('click', () => {
                if (validateStep(currentStep)) {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        updateStepUI();
                    } else {
                        // Go to checkout / payment
                        window.location.href = 'payment.php';
                    }
                }
            });
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateStepUI();
                }
            });
        }

        // Functions declarations for Steps Wizard
        function updateStepUI() {
            // Toggle panels visibility
            panels.forEach(p => p.style.display = 'none');
            const activePanel = document.getElementById(`step-panel-${currentStep}`);
            if (activePanel) activePanel.style.display = 'block';

            // Update steps classes
            stepItems.forEach(item => {
                const stepNum = parseInt(item.dataset.step);
                item.classList.remove('active', 'completed');
                if (stepNum === currentStep) {
                    item.classList.add('active');
                } else if (stepNum < currentStep) {
                    item.classList.add('completed');
                }
            });

            // Adjust navigation buttons visibility
            if (btnPrev) {
                btnPrev.style.display = currentStep === 1 ? 'none' : 'inline-flex';
            }
            if (btnNext) {
                btnNext.innerText = currentStep === totalSteps ? 'Proceed to Payment' : 'Next Step';
            }
        }

        function validateStep(step) {
            if (step === 1) {
                if (!inputConsoleId.value) {
                    alert('Harap pilih salah satu jenis console terlebih dahulu.');
                    return false;
                }
            } else if (step === 2) {
                if (!inputRoomId.value) {
                    alert('Harap pilih salah satu tipe room terlebih dahulu.');
                    return false;
                }
                if (!selectGame.value) {
                    alert('Harap pilih game utama yang ingin dimainkan.');
                    return false;
                }
                const people = parseInt(inputPeopleCount.value);
                const selectedRoomCard = document.querySelector('.room-card.selected');
                const max = parseInt(selectedRoomCard.dataset.maxPeople);
                if (isNaN(people) || people < 1 || people > max) {
                    alert(`Jumlah orang harus antara 1 sampai ${max} orang.`);
                    return false;
                }
            } else if (step === 3) {
                if (!inputDate.value) {
                    alert('Harap pilih tanggal booking.');
                    return false;
                }
                if (!inputStartTime.value) {
                    alert('Harap masukkan jam mulai booking.');
                    return false;
                }
                if (!inputDuration.value) {
                    alert('Harap tentukan durasi booking.');
                    return false;
                }
                
                // Operational hour validation: 10:00 - 23:00
                const startHour = parseInt(inputStartTime.value.split(':')[0]);
                const durationVal = parseInt(inputDuration.value);
                const endHour = startHour + durationVal;
                
                if (startHour < 10 || startHour >= 23) {
                    alert('Jam operasional adalah 10:00 sampai 23:00. Jam mulai minimal pukul 10:00.');
                    return false;
                }
                
                if (endHour > 23 || (endHour === 23 && parseInt(inputStartTime.value.split(':')[1]) > 0)) {
                    alert('Booking melebihi jam operasional toko (maksimal sampai jam 23:00). Silakan kurangi durasi atau majukan jam mulai.');
                    return false;
                }

                if (durationVal < 1 || durationVal > 12) {
                    alert('Durasi booking minimal 1 jam dan maksimal 12 jam.');
                    return false;
                }

                // Check conflict status loaded dynamically
                const isConflict = btnNext.dataset.conflict === 'true';
                if (isConflict) {
                    alert('Slot waktu tersebut bentrok/sudah dibooking. Silakan pilih waktu atau tipe ruangan lain.');
                    return false;
                }
            }
            return true;
        }

        // Fetch games using AJAX
        function fetchGames(consoleId) {
            if(!selectGame) return;
            selectGame.innerHTML = '<option value="">Memuat game...</option>';
            
            fetch(`ajax/get_games.php?console_id=${consoleId}`)
                .then(response => response.json())
                .then(data => {
                    selectGame.innerHTML = '<option value="">-- Pilih Game Utama --</option>';
                    data.forEach(game => {
                        const opt = document.createElement('option');
                        opt.value = game.id;
                        opt.textContent = game.name;
                        selectGame.appendChild(opt);
                    });
                })
                .catch(err => {
                    console.error('Failed to fetch games', err);
                    selectGame.innerHTML = '<option value="">Gagal memuat game</option>';
                });
        }

        // Availability checking AJAX
        function checkAvailability() {
            const date = inputDate.value;
            const time = inputStartTime.value;
            const dur = inputDuration.value;
            const room = inputRoomId.value;
            const cons = inputConsoleId.value;
            const statusBox = document.getElementById('availability-status');
            
            if(!statusBox) return;
            statusBox.innerHTML = '<span class="text-muted">Mengecek ketersediaan...</span>';
            
            fetch(`ajax/check_slot.php?date=${date}&time=${time}&duration=${dur}&room_id=${room}&console_id=${cons}`)
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        statusBox.innerHTML = '<span class="alert alert-success d-flex align-center" style="display:inline-flex !important; margin:0;"><span class="mr-2">✓</span> Slot waktu tersedia</span>';
                        btnNext.dataset.conflict = 'false';
                    } else {
                        statusBox.innerHTML = '<span class="alert alert-danger d-flex align-center" style="display:inline-flex !important; margin:0;"><span class="mr-2">✗</span> Slot waktu tidak tersedia (bentrok)</span>';
                        btnNext.dataset.conflict = 'true';
                    }
                })
                .catch(err => {
                    console.error('Failed to check availability', err);
                    statusBox.innerHTML = '<span class="text-danger">Gagal mengecek ketersediaan slot</span>';
                });
        }

        // Send AJAX updates to server to store in session
        function saveSessionData(data) {
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }
            fetch('ajax/save_session.php', {
                method: 'POST',
                body: formData
            }).catch(err => console.error('Failed to save session data', err));
        }

        // Food Category selection filter
        const foodTabButtons = document.querySelectorAll('.food-tab-btn');
        const foodItems = document.querySelectorAll('.food-item');
        
        foodTabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                foodTabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const cat = btn.dataset.category;
                foodItems.forEach(item => {
                    if (cat === 'all' || item.dataset.category === cat) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Food quantities triggers
        const qtyBtns = document.querySelectorAll('.qty-btn');
        qtyBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const isAdd = btn.classList.contains('qty-add');
                const foodItem = btn.closest('.food-item');
                const foodId = foodItem.dataset.foodId;
                const qtyValNode = foodItem.querySelector('.qty-val');
                let currentQty = parseInt(qtyValNode.innerText);
                
                if (isAdd) {
                    currentQty++;
                } else {
                    if (currentQty > 0) currentQty--;
                }
                
                qtyValNode.innerText = currentQty;
                
                // Sync food update in session & recalculate
                updateFoodSession(foodId, currentQty);
            });
        });

        function updateFoodSession(foodId, qty) {
            const formData = new FormData();
            formData.append('food_id', foodId);
            formData.append('qty', qty);
            
            fetch('ajax/save_session.php?action=update_food', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                recalculateTotal();
            })
            .catch(err => console.error('Failed to update food quantity in session', err));
        }
        
        function recalculateTotal() {
            const date = inputDate.value;
            const time = inputStartTime.value;
            const dur = inputDuration.value || 1;
            const room = inputRoomId.value;
            const promoInput = document.getElementById('promo_code');
            const promoCode = promoInput ? promoInput.value : '';

            // Update local time displays on sidebar
            updateSummaryText('summary-date', date || '-');
            updateSummaryText('summary-time', time ? `${time} (${dur} Jam)` : '-');
            
            // Hitung ke server
            fetch(`ajax/calculate_total.php?duration=${dur}&room_id=${room}&promo_code=${promoCode}`)
                .then(response => response.json())
                .then(data => {
                    // Update prices
                    updateSummaryText('summary-room-total', formatRupiah(data.room_total));
                    updateSummaryText('summary-food-total', formatRupiah(data.food_total));
                    updateSummaryText('summary-discount', formatRupiah(data.discount));
                    updateSummaryText('summary-grand-total', formatRupiah(data.grand_total));
                    
                    // Render food items list in summary sidebar
                    const foodListContainer = document.getElementById('summary-food-list');
                    if (foodListContainer) {
                        foodListContainer.innerHTML = '';
                        if (data.foods_selected && data.foods_selected.length > 0) {
                            data.foods_selected.forEach(f => {
                                const li = document.createElement('li');
                                li.textContent = `${f.name} x${f.qty} (${formatRupiah(f.subtotal)})`;
                                foodListContainer.appendChild(li);
                            });
                        }
                    }
                })
                .catch(err => console.error('Failed to calculate booking totals', err));
        }

        // Apply promo code button triggers
        const btnPromo = document.getElementById('btn-apply-promo');
        if (btnPromo) {
            btnPromo.addEventListener('click', () => {
                recalculateTotal();
                alert('Kode promo diterapkan!');
            });
        }
    }

    // 3. Payment Method Switcher (in payment.php)
    const methodCards = document.querySelectorAll('.method-card');
    const inputPaymentMethod = document.getElementById('payment_method');
    const qrisSection = document.getElementById('payment-qris-details');
    const tfSection = document.getElementById('payment-tf-details');
    const ewalletSection = document.getElementById('payment-ewallet-details');

    if (methodCards.length > 0) {
        methodCards.forEach(card => {
            card.addEventListener('click', () => {
                methodCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                
                const method = card.dataset.method;
                if(inputPaymentMethod) inputPaymentMethod.value = method;
                
                // Show relative detail box
                if(qrisSection) qrisSection.style.display = method === 'QRIS' ? 'block' : 'none';
                if(tfSection) tfSection.style.display = method === 'Transfer Bank' ? 'block' : 'none';
                if(ewalletSection) ewalletSection.style.display = method === 'E-Wallet' ? 'block' : 'none';
            });
        });
    }

    // Helper functions
    function updateSummaryText(id, text) {
        const el = document.getElementById(id);
        if (el) el.innerText = text;
    }

    function formatRupiah(num) {
        const val = parseFloat(num);
        if (isNaN(val)) return 'Rp0';
        return 'Rp' + val.toLocaleString('id-ID').replace(/,/g, '.');
    }
});
