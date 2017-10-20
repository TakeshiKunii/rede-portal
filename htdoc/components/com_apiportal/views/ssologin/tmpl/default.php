<style>
	body {
		color: #10181F; }

	
	.loader {
		display: inline-block;
		width: 1.875rem;
		margin-left: 0.625rem;
		height: 0.625rem; }
	
	.loader.big {
		width: 3.75rem;
		margin-left: 0.3125rem;
		height: 1.25rem; }
	
	.loader.linecentered {
		position: absolute;
		left: 50%;
		transform: translate(-50%, -50%);
		margin-left: 0; }
	
	.loader span, .loader:before, .loader:after {
		display: inline-block;
		float: left;
		width: 33%;
		height: 99%;
		border-radius: 50%;
		margin: 0%;
		opacity: 0; }
	
	.loader span {
		display: block;
		background: #3D4051;
		text-indent: -9000px;
		overflow: hidden; }
	
	.loader:before, .loader:after, .loader span:before, .loader span:after {
		content: '';
		background: #407CA0; }
	
	.loader:before {
		animation: loader-dot-animation 1.125s ease infinite 0s; }
	
	.loader span {
		animation: loader-dot-animation 1.125s ease infinite 0.37463s; }
	
	.loader:after {
		animation: loader-dot-animation 1.125s ease infinite 0.75038s; }

	@keyframes loader-dot-animation {
		0% {
			transform: scale(0);
			opacity: 0; }
		50% {
			transform: scale(1);
			opacity: 1; } }

	
	.spinner {
		display: inline-block;
		margin-left: 0.3125rem;
		margin-right: 0.3125rem;
		height: 2.50625rem;
		width: 2.50625rem;
		transform: translate(0, -25%); }
	
	.spinner span {
		position: absolute;
		display: inline-block;
		font-size: 0px;
		color: transparent;
		margin-left: -1.25rem;
		margin-top: 1.25rem; }
	
	.spinner.big {
		height: 5.00625rem;
		width: 5.00625rem; }
	
	.spinner.big span {
		margin-left: -2.5rem;
		margin-top: 2.5rem; }
	
	.spinner.linecentered {
		position: absolute;
		left: 50%;
		transform: translate(-50%, -50%);
		margin-left: 0; }
	
	.spinner.pagecentered {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		margin-left: 0;
		background: rgba(255, 255, 255, 0.8);
		padding: 0.625rem;
		border-radius: 0.625rem; }
	
	.spinner.pagecentered.big {
		padding: 1.25rem;
		border-radius: 1.25rem; }
	
	.spinner:before, .spinner:after,
	.spinner span:after,
	.spinner span:before {
		content: '';
		display: inline-block;
		height: 1.25rem;
		width: 1.25rem;
		background: #407CA0;
		border-radius: 50%;
		transform: scale(0.9);
		margin: 0;
		opacity: 0; }
	
	.spinner.big:before, .spinner.big:after,
	.spinner.big span:after,
	.spinner.big span:before {
		height: 2.5rem;
		width: 2.5rem; }
	
	.spinner:before {
		animation: spinner-dot-animation 1.125s ease infinite 0s; }
	
	.spinner:after {
		animation: spinner-dot-animation 1.125s ease infinite 0.28125s; }
	
	.spinner span:after {
		animation: spinner-dot-animation 1.125s ease infinite 0.5625s; }
	
	.spinner span:before {
		animation: spinner-dot-animation 1.125s ease infinite 0.84375s; }

	@keyframes spinner-dot-animation {
		0% {
			transform: scale(0);
			opacity: 0;
			background-color: #407CA0; }
		45% {
			transform: scale(0.9);
			opacity: 1;
			background-color: #3D4051; }
		55% {
			opacity: 1; }
		90% {
			opacity: 0; } }
</style>

<div style="width: 100%;position: relative;top: 250px;">
	<div style="margin: 0 auto;width: 300px;text-align: center;">
		Please wait. <div class="loader"><span>Redirecting...</span></div>
	</div>
</div>