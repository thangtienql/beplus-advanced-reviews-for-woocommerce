const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const { join } = require( 'node:path' );
const { test } = require( 'node:test' );
const { runInNewContext } = require( 'node:vm' );

const viewScript = readFileSync( join( __dirname, '../blocks/advanced-review/view.js' ), 'utf8' );

function element() {
	const attributes = new Map();
	const classes = new Set();
	return {
		listeners: {},
		children: [ {} ],
		classList: {
			toggle( name, enabled ) { enabled ? classes.add( name ) : classes.delete( name ); },
			contains: ( name ) => classes.has( name ),
		},
		addEventListener( type, listener ) { this.listeners[ type ] = listener; },
		setAttribute( name, value ) { attributes.set( name, value ); },
		getAttribute( name ) { return attributes.get( name ); },
		hasAttribute( name ) { return attributes.has( name ); },
		removeAttribute( name ) { attributes.delete( name ); },
		querySelector() { return null; },
		appendChild( node ) { this.innerHTML = node.textContent; },
		append( ...nodes ) { this.children.push( ...nodes ); },
		before( node ) { this.previousElementSibling = node; },
	};
}

function mountReviews( fetch = async () => emptyResponse, canVoteHelpful = false ) {
	const prefix = '.beplus-advanced-reviews-for-woocommerce';
	const list = element();
	const listClickHandlers = [];
	list.addEventListener = ( type, listener ) => {
		if ( type === 'click' ) listClickHandlers.push( listener );
	};
	const toolbar = element();
	const rating = { ...element(), value: '0' };
	const sort = { ...element(), value: 'newest' };
	const media = element();
	const verified = element();
	const elements = new Map( [
		[ prefix + '__list', list ],
		[ prefix + '__filter-controls', toolbar ],
		[ prefix + '__filter-rating-select', rating ],
		[ prefix + '__sort-select', sort ],
		[ prefix + '__filter-chip[data-filter="images"]', media ],
		[ prefix + '__filter-chip[data-filter="verified"]', verified ],
	] );
	const block = {
		dataset: { productId: '31' },
		querySelector: ( selector ) => elements.get( selector ),
	};
	const document = {
		...element(),
		createElement: element,
		createTextNode: ( textContent ) => ( { textContent } ),
		querySelectorAll: () => [ block ],
	};
	runInNewContext( viewScript, {
		document,
		window: { location: { hash: '' } },
		AbortController,
		URL,
		fetch,
		setTimeout,
		clearTimeout,
		bparfwData: {
			restUrl: 'https://example.test/',
			canVoteHelpful,
			i18n: {
				noReviews: 'No reviews yet.',
				noMatchingReviews: 'No reviews match your filters.',
				updatingReviews: 'Updating reviews…',
				reviewsLoadError: 'Could not load reviews.',
				helpful: 'Helpful',
				helpfulAdded: 'Helpful vote added.',
				helpfulRemoved: 'Helpful vote removed.',
				helpfulError: 'Could not save your vote. Try again.',
				helpfulSignIn: 'Sign in to vote.',
			},
		},
	} );
	document.listeners.DOMContentLoaded();
	return { list, toolbar, rating, sort, media, verified, listClickHandlers };
}

const emptyResponse = { ok: true, json: async () => ( { reviews: [], pages: 0 } ) };
const flush = () => new Promise( setImmediate );

async function renderEmptyList( control, value ) {
	const { list, rating, sort, media, verified } = mountReviews();
	await flush();
	if ( control === 'rating' || control === 'sort' ) {
		const select = control === 'rating' ? rating : sort;
		select.value = value;
		select.listeners.change();
	} else if ( control ) {
		( control === 'media' ? media : verified ).listeners.click();
	}
	await flush();
	return list.innerHTML;
}

for ( const [ control, value ] of [ [ 'rating', '4' ], [ 'media' ], [ 'verified' ] ] ) {
	test( `empty results with the ${ control } filter use the no-match message`, async () => {
		assert.match( await renderEmptyList( control, value ), />No reviews match your filters\.<\/p>/ );
	} );
}

test( 'an unfiltered empty list uses the no-reviews message', async () => {
	assert.match( await renderEmptyList(), />No reviews yet\.<\/p>/ );
} );

test( 'changing only the sort order does not count as filtering', async () => {
	assert.match( await renderEmptyList( 'sort', 'oldest' ), />No reviews yet\.<\/p>/ );
} );

test( 'a review footer groups the date and helpful action with a separate zero count', async () => {
	const review = { id: 13, rating: 5, date_human: '3 days ago', helpful_count: 0, viewer_has_voted: false };
	const { list } = mountReviews( async () => ( { ok: true, json: async () => ( { reviews: [ review ], pages: 1 } ) } ) );
	await flush();
	const footer = list.innerHTML.match( /<footer class="[^"]+__review-footer">([\s\S]*?)<\/footer>/ )?.[ 1 ];
	assert.ok( footer, 'review has a footer' );
	assert.match( footer, /__review-date">3 days ago<\/div>[\s\S]*data-review-id="13" aria-pressed="false"/ );
	assert.match( footer, /<span class="[^"]+__helpful-count" data-helpful-count>0<\/span><\/button><div class="[^"]+__helpful-status" role="status"><\/div>/ );
} );

test( 'a review without helpful voting still renders its date without an empty action', async () => {
	const review = { id: 13, rating: 5, date_human: '3 days ago' };
	const { list } = mountReviews( async () => ( { ok: true, json: async () => ( { reviews: [ review ], pages: 1 } ) } ) );
	await flush();
	assert.match( list.innerHTML, /__review-footer"><div class="[^"]+__review-date">3 days ago<\/div><\/footer>/ );
	assert.doesNotMatch( list.innerHTML, /__helpful-button/ );
} );

test( 'successful helpful changes are announced without visible status, but errors remain visible', async () => {
	let failVote = false;
	const fetch = async ( url, options ) => {
		if ( options.method !== 'PUT' ) return emptyResponse;
		if ( failVote ) return { ok: false, status: 500 };
		const { voted } = JSON.parse( options.body );
		return { ok: true, json: async () => ( { helpful_count: voted ? 1 : 0, viewer_has_voted: voted } ) };
	};
	const { list, listClickHandlers } = mountReviews( fetch, true );
	await flush();
	const status = element();
	const count = { textContent: '0' };
	const button = { ...element(), dataset: { reviewId: '13' }, nextElementSibling: status, querySelector: () => count };
	button.setAttribute( 'aria-pressed', 'false' );
	list.querySelector = () => button;
	list.querySelectorAll = () => [ button ];
	const click = () => Promise.all( listClickHandlers.map( ( listener ) => listener( {
		target: { closest: ( selector ) => selector.endsWith( '__helpful-button' ) ? button : null },
	} ) ) );
	const announceOnly = 'beplus-advanced-reviews-for-woocommerce__helpful-status--announce-only';

	await click();
	assert.equal( count.textContent, 1 );
	assert.equal( button.getAttribute( 'aria-pressed' ), 'true' );
	assert.equal( status.textContent, 'Helpful vote added.' );
	assert.equal( status.classList.contains( announceOnly ), true );

	await click();
	assert.equal( count.textContent, 0 );
	assert.equal( button.getAttribute( 'aria-pressed' ), 'false' );
	assert.equal( status.textContent, 'Helpful vote removed.' );
	assert.equal( status.classList.contains( announceOnly ), true );

	failVote = true;
	await click();
	assert.equal( count.textContent, 0 );
	assert.equal( status.textContent, 'Could not save your vote. Try again.' );
	assert.equal( status.classList.contains( announceOnly ), false );
} );

test( 'filter updates keep their message screen-reader only and delay the toolbar spinner', async () => {
	const pending = [];
	const { list, toolbar, rating, sort } = mountReviews( () => new Promise( ( resolve ) => pending.push( resolve ) ) );
	pending.shift()( emptyResponse );
	await flush();
	const existingReviews = list.innerHTML;
	const status = list.previousElementSibling;
	const [ , inlineSpinner, message, retry ] = status.children;
	const spinner = () => toolbar.children.find( ( item ) => item.className?.endsWith( '__filter-spinner' ) );

	rating.value = '4';
	rating.listeners.change();
	assert.equal( status.classList.contains( 'screen-reader-text' ), true );
	assert.equal( inlineSpinner.hidden, true );
	assert.equal( message.textContent, 'Updating reviews…' );
	assert.equal( list.innerHTML, existingReviews );
	assert.equal( list.getAttribute( 'aria-busy' ), 'true' );
	assert.equal( spinner()?.hidden, true );
	pending.shift()( emptyResponse );
	await flush();
	await new Promise( ( resolve ) => setTimeout( resolve, 220 ) );
	assert.equal( spinner().hidden, true, 'a completed request cannot show a stale spinner' );

	sort.value = 'oldest';
	sort.listeners.change();
	await new Promise( ( resolve ) => setTimeout( resolve, 220 ) );
	assert.equal( spinner().hidden, false );
	assert.equal( spinner().getAttribute( 'aria-hidden' ), 'true' );

	rating.value = '5';
	rating.listeners.change();
	assert.equal( spinner().hidden, true, 'a replacement request starts its own delay' );
	pending.shift()( emptyResponse );
	await flush();
	assert.equal( list.getAttribute( 'aria-busy' ), 'true', 'obsolete responses cannot clear the latest state' );
	pending.shift()( { ok: false } );
	await flush();
	assert.equal( spinner().hidden, true );
	assert.equal( status.classList.contains( 'screen-reader-text' ), false );
	assert.equal( message.textContent, 'Could not load reviews.' );
	assert.equal( retry.hidden, false );
} );
