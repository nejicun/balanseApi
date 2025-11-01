<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Баланс пользователей</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f9fafb;
        }
        .container {
            display: flex;
            gap: 30px;
        }
        .users-list {
            width: 300px;
            background: white;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .user-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .user-item:hover {
            background: #f1f5f9;
        }
        .user-item.active {
            background: #dbeafe;
            font-weight: bold;
        }
        .main-panel {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .selected-user {
            margin-bottom: 24px;
        }
        .actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            max-width: 400px;
        }
        .btn {
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-balance {
            background: #3b82f6;
            color: white;
        }
        .btn-balance:hover {
            background: #2563eb;
        }
        .btn-inactive {
            background: #e5e7eb;
            color: #6b7280;
            cursor: not-allowed;
        }
        .balance-result {
            margin-top: 20px;
            padding: 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Управление балансом</h1>

    <div class="container">
        <!-- Список пользователей -->
        <div class="users-list">
            <h3>Пользователи</h3>
            @foreach($users as $user)
                <div class="user-item" data-id="{{ $user->id }}" onclick="selectUser({{ $user->id }}, '{{ $user->name }}', {{ $user->balance }})">
                    {{ $user->name }} — {{ number_format($user->balance, 2, ',', ' ') }} ₽
                </div>
            @endforeach
        </div>

        <!-- Центральная панель -->
        <div class="main-panel">
            <div class="selected-user" id="selectedUser">
                <p>Выберите пользователя</p>
            </div>

        <div class="actions">
            <button class="btn btn-balance" onclick="showBalance()">Показать баланс</button>
            <button class="btn btn-balance" onclick="openDepositModal()">Пополнить</button>
            <button class="btn btn-balance" onclick="openWithdrawModal()">Списать</button>
            <button class="btn btn-balance" onclick="openTransferModal()">Перевести</button>
            <button class="btn btn-balance" style="grid-column: span 2;" onclick="showTransactions()">
        История операций
            </button>
        </div>

        <!-- Модальные окна -->
        <div id="depositModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div style="background:white; margin:100px auto; padding:20px; width:400px; border-radius:8px;">
                <h3>Пополнить баланс</h3>
                <input type="number" id="depositAmount" placeholder="Сумма" step="0.01" min="0.01" style="width:100%; padding:8px; margin:8px 0;">
        <input type="text" id="depositComment" placeholder="Комментарий (необязательно)" style="width:100%; padding:8px; margin:8px 0;">
        <button onclick="doDeposit()" style="background:#10b981; color:white; padding:8px 16px; border:none; border-radius:4px;">Пополнить</button>
                <button onclick="closeDepositModal()" style="margin-left:10px; padding:8px 16px;">Отмена</button>
            </div>
        </div>

            <div id="balanceResult"></div>
            </div>
        </div>

        <div id="withdrawModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div style="background:white; margin:100px auto; padding:20px; width:400px; border-radius:8px;">
                <h3>Списать средства</h3>
                <input type="number" id="withdrawAmount" placeholder="Сумма" step="0.01" min="0.01" style="width:100%; padding:8px; margin:8px 0;">
                <input type="text" id="withdrawComment" placeholder="Комментарий (необязательно)" style="width:100%; padding:8px; margin:8px 0;">
                <button onclick="doWithdraw()" style="background:#ef4444; color:white; padding:8px 16px; border:none; border-radius:4px;">Списать</button>
                <button onclick="closeWithdrawModal()" style="margin-left:10px; padding:8px 16px;">Отмена</button>
            </div>
        </div>

        <div id="transferModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div style="background:white; margin:100px auto; padding:20px; width:400px; border-radius:8px;">
                <h3>Перевод между пользователями</h3>
                <p>От кого (ID): <strong id="transferFromId"></strong></p>
                <input type="number" id="transferToId" placeholder="ID получателя" min="1" style="width:100%; padding:8px; margin:8px 0;">
                <input type="number" id="transferAmount" placeholder="Сумма" step="0.01" min="0.01" style="width:100%; padding:8px; margin:8px 0;">
                <input type="text" id="transferComment" placeholder="Комментарий (необязательно)" style="width:100%; padding:8px; margin:8px 0;">
                <button onclick="doTransfer()" style="background:#8b5cf6; color:white; padding:8px 16px; border:none; border-radius:4px;">Перевести</button>
                <button onclick="closeTransferModal()" style="margin-left:10px; padding:8px 16px;">Отмена</button>
            </div>
        </div>
 <script>
    let selectedUserId = null;

    function selectUser(id, name) {
        selectedUserId = id;
        document.getElementById('selectedUser').innerHTML = `
            <h2>${name}</h2>
            <p>ID: ${id}</p>
            <p><em>Баланс скрыт. Нажмите "Показать баланс".</em></p>
        `;
        document.getElementById('balanceResult').innerHTML = '';
    }

    async function showBalance() {
        if (!selectedUserId) {
            alert('Сначала выберите пользователя');
            return;
        }

        try {
            // Запрос к API
            const response = await fetch(`/api/balance/${selectedUserId}`);
            console.log(response)
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}`);
            }

            const data = await response.json();
            document.getElementById('balanceResult').innerHTML = `
                <div class="balance-result">
                    Баланс: ${parseFloat(data.balance).toFixed(2)} ₽
                </div>
            `;
        } catch (err) {
            console.error('Ошибка:', err);
            document.getElementById('balanceResult').innerHTML = `
                <div style="color: red;">Ошибка: ${err.message}</div>
            `;
        }
    }



function openDepositModal() {
    if (!selectedUserId) {
        alert('Сначала выберите пользователя');
        return;
    }
    document.getElementById('depositModal').style.display = 'block';
}

function closeDepositModal() {
    document.getElementById('depositModal').style.display = 'none';
}

async function doDeposit() {
    const amount = parseFloat(document.getElementById('depositAmount').value);
    const comment = document.getElementById('depositComment').value.trim() || null;

    if (!amount || amount <= 0) {
        alert('Введите корректную сумму');
        return;
    }

    try {
        const response = await fetch('/api/deposit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: selectedUserId,
                amount: amount,
                comment: comment
            })
        });

        const data = await response.json();

        if (response.ok) {
            closeDepositModal();
            document.getElementById('balanceResult').innerHTML = `
                <div class="balance-result">
                    Баланс обновлён: ${data.balance.toFixed(2)} ₽
                </div>
            `;
 
            alert('Пополнение успешно!');
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    } catch (err) {
        console.error('Ошибка:', err);
        alert('Ошибка сети');
    }
}
function openWithdrawModal() {
    if (!selectedUserId) {
        alert('Сначала выберите пользователя');
        return;
    }
    document.getElementById('withdrawModal').style.display = 'block';
}

function closeWithdrawModal() {
    document.getElementById('withdrawModal').style.display = 'none';
}

async function doWithdraw() {
    const amount = parseFloat(document.getElementById('withdrawAmount').value);
    const comment = document.getElementById('withdrawComment').value.trim() || null;

    if (!amount || amount <= 0) {
        alert('Введите корректную сумму');
        return;
    }

    try {
        const response = await fetch('/api/withdraw', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: selectedUserId,
                amount: amount,
                comment: comment
            })
        });

        const data = await response.json();

        if (response.ok) {
            closeWithdrawModal();
            document.getElementById('balanceResult').innerHTML = `
                <div class="balance-result">
                    Баланс обновлён: ${data.balance.toFixed(2)} ₽
                </div>
            `;
            alert('Списание успешно!');
        } else {
            let errorMsg = data.error || 'Неизвестная ошибка';
            if (response.status === 409) errorMsg = 'Недостаточно средств';
            if (response.status === 404) errorMsg = 'Пользователь не найден';
            alert('Ошибка: ' + errorMsg);
        }
    } catch (err) {
        console.error('Ошибка:', err);
        alert('Ошибка сети');
    }
}

function openTransferModal() {
    if (!selectedUserId) {
        alert('Сначала выберите отправителя');
        return;
    }
    document.getElementById('transferFromId').textContent = selectedUserId;
    document.getElementById('transferModal').style.display = 'block';
}

function closeTransferModal() {
    document.getElementById('transferModal').style.display = 'none';
}

async function doTransfer() {
    const toId = parseInt(document.getElementById('transferToId').value);
    const amount = parseFloat(document.getElementById('transferAmount').value);
    const comment = document.getElementById('transferComment').value.trim() || null;

    if (!toId || toId <= 0) {
        alert('Введите корректный ID получателя');
        return;
    }
    if (!amount || amount <= 0) {
        alert('Введите корректную сумму');
        return;
    }
    if (toId === selectedUserId) {
        alert('Нельзя переводить самому себе');
        return;
    }

    try {
        const response = await fetch('/api/transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                from_user_id: selectedUserId,
                to_user_id: toId,
                amount: amount,
                comment: comment
            })
        });

        const data = await response.json();

        if (response.ok) {
            closeTransferModal();
            document.getElementById('balanceResult').innerHTML = `
                <div class="balance-result">
                    Перевод выполнен!<br>
                    Ваш баланс: ${data.from_balance.toFixed(2)} ₽
                </div>
            `;
            alert('Перевод успешно выполнен!');
        } else {
            let errorMsg = data.error || 'Неизвестная ошибка';
            if (response.status === 409) errorMsg = 'Недостаточно средств';
            if (response.status === 404) errorMsg = 'Пользователь не найден';
            alert('Ошибка: ' + errorMsg);
        }
    } catch (err) {
        console.error('Ошибка:', err);
        alert('Ошибка сети');
    }
}
async function showTransactions() {
    if (!selectedUserId) {
        alert('Сначала выберите пользователя');
        return;
    }

    try {
        const response = await fetch(`/api/transactions/${selectedUserId}`);
        const data = await response.json();

        if (!response.ok) {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            return;
        }

        let html = `<h3>История операций (${data.transactions.length})</h3><div style="max-height:300px; overflow-y:auto;">`;
        if (data.transactions.length === 0) {
            html += '<p>Нет транзакций</p>';
        } else {
            data.transactions.forEach(t => {
                const typeColor = 
                    t.type === 'deposit' || t.type === 'transfer_in' ? '#10b981' :
                    t.type === 'withdraw' || t.type === 'transfer_out' ? '#ef4444' : '#6b7280';
                html += `
                    <div style="border-bottom:1px solid #eee; padding:8px 0;">
                        <strong style="color:${typeColor}">${t.type}</strong> 
                        ${t.amount.toFixed(2)} ₽
                        <br><small>
                            Баланс после: ${t.balance_after.toFixed(2)} ₽
                            ${t.comment ? ` — ${t.comment}` : ''}
                            <br>${t.created_at}
                        </small>
                    </div>
                `;
            });
        }
        html += '</div><button onclick="closeTransactions()" style="margin-top:10px; padding:6px 12px;">Закрыть</button>';

        document.getElementById('balanceResult').innerHTML = html;
    } catch (err) {
        console.error('Ошибка:', err);
        alert('Ошибка сети');
    }
}

function closeTransactions() {
    document.getElementById('balanceResult').innerHTML = '';
}

</script>
</body>
</html>