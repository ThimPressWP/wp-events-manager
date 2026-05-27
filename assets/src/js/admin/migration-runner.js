( function () {
	const data = window.wpemsMigration;

	if ( ! data ) {
		return;
	}

	const root = document.getElementById( 'wpems-migrate-bookings' );

	if ( ! root ) {
		return;
	}

	const pendingEl = root.querySelector( '[data-count="pending"]' );
	const importedEl = root.querySelector( '[data-count="imported"]' );
	const failedEl = root.querySelector( '[data-count="failed"]' );
	const progress = root.querySelector( '.wpems-migration-progress .bar' );
	const log = root.querySelector( '.wpems-migration-log' );
	const stopBtn = root.querySelector( '[data-action="stop"]' );
	const autoBtn = root.querySelector( '[data-action="auto"]' );
	const batchSize = data.batchSize || 200;

	let total = 0;
	let importedSession = 0;
	let failedSession = 0;
	let autoRunning = false;

	function appendLog( line ) {
		log.textContent += ( log.textContent ? '\n' : '' ) + line;
		log.scrollTop = log.scrollHeight;
	}

	async function call( action, payload = {} ) {
		const body = new URLSearchParams( {
			action: 'wpems_migration_' + action,
			nonce: data.nonce,
			...payload,
		} );

		const response = await window.fetch( data.ajaxUrl, {
			method: 'POST',
			body,
			credentials: 'same-origin',
		} );

		const json = await response.json();

		if ( ! json.success ) {
			throw new Error( json.data?.message || 'unknown_error' );
		}

		return json.data;
	}

	function updateProgress( remaining ) {
		if ( 0 === total ) {
			total = remaining + importedSession + failedSession;
		}

		const done = Math.max( 0, total - remaining );
		const percent = total > 0 ? Math.min( 100, Math.round( ( done / total ) * 100 ) ) : 0;

		progress.style.width = percent + '%';
		progress.dataset.progress = String( percent );
		pendingEl.textContent = String( remaining );
	}

	async function countPending() {
		const result = await call( 'count' );

		pendingEl.textContent = String( result.pending );
		total = result.pending + importedSession + failedSession;
		updateProgress( result.pending );
		appendLog( 'Pending: ' + result.pending );
	}

	async function runOnce() {
		const result = await call( 'run_batch', { batch_size: batchSize } );

		importedSession += result.imported;
		failedSession += result.failed;
		importedEl.textContent = String( importedSession );
		failedEl.textContent = String( failedSession );
		updateProgress( result.remaining );
		appendLog(
			`Batch: imported ${ result.imported }, skipped ${ result.skipped }, failed ${ result.failed }, remaining ${ result.remaining }`
		);

		if ( result.failed > 0 && result.errors ) {
			Object.entries( result.errors ).forEach( ( [ id, message ] ) => {
				appendLog( `- post ${ id }: ${ message }` );
			} );
		}

		return result.remaining;
	}

	function setAutoRunning( running ) {
		autoRunning = running;
		stopBtn.disabled = ! running;
		autoBtn.disabled = running;
	}

	async function autoRun() {
		setAutoRunning( true );

		try {
			while ( autoRunning ) {
				const remaining = await runOnce();

				if ( 0 === remaining ) {
					appendLog( 'All bookings imported.' );
					break;
				}
			}
		} catch ( error ) {
			appendLog( 'Error: ' + error.message );
		} finally {
			setAutoRunning( false );
		}
	}

	async function verify() {
		const result = await call( 'verify' );

		if ( result.ok ) {
			appendLog( 'Verify OK' );
			return;
		}

		appendLog( `Verify FAILED: legacy=${ result.legacy_total } new=${ result.new_total } diff=${ result.diff_count }` );
	}

	async function rebuildInventory() {
		const result = await call( 'rebuild_inventory' );

		appendLog( `Inventory rebuilt for ${ result.events_processed } events.` );
	}

	root.addEventListener( 'click', async ( event ) => {
		const action = event.target?.dataset?.action;

		if ( ! action ) {
			return;
		}

		try {
			if ( 'count' === action ) {
				await countPending();
			} else if ( 'run' === action ) {
				await runOnce();
			} else if ( 'auto' === action ) {
				await autoRun();
			} else if ( 'stop' === action ) {
				setAutoRunning( false );
			} else if ( 'verify' === action ) {
				await verify();
			} else if ( 'rebuild' === action ) {
				await rebuildInventory();
			}
		} catch ( error ) {
			appendLog( 'Error: ' + error.message );
			setAutoRunning( false );
		}
	} );
} )();
