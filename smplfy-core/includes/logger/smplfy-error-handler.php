<?php
namespace SmplfyCore;
use Throwable;
function smplfy_logger_custom_error_handler( $err_no, $err_str, $err_file, $err_line ): bool {

	/**
	 *  When calling SMPLFY_Log within this error handler we are passing the log_to_file parameter as false to avoid logging the same error twice.
	 *  We are returning false at the end of this function to allow the default PHP error handler to be called
	 *  which will already be logging the error to the error log file. The error will still be sent to DataDog.
	 */
	switch ( $err_no ) {
		case E_ERROR:
		case E_USER_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_RECOVERABLE_ERROR:
			$message = smplfy_logger_generate_message( "PHP Fatal error", $err_str, $err_file, $err_line );
			SMPLFY_Log::error( $message, null, false );
			break;

		case E_PARSE:
			$message = smplfy_logger_generate_message( "PHP Parse error", $err_str, $err_file, $err_line );
			SMPLFY_Log::error( $message, null, false );
			break;

		case E_WARNING:
		case E_CORE_WARNING:
		case E_USER_WARNING:
		case E_COMPILE_WARNING:
			$message = smplfy_logger_generate_message( "PHP Warning", $err_str, $err_file, $err_line );
			SMPLFY_Log::warn( $message, null, false );
			break;

		case E_NOTICE:
		case E_USER_NOTICE:
			$message = smplfy_logger_generate_message( "PHP Notice", $err_str, $err_file, $err_line );
			SMPLFY_Log::info( $message, null, false );
			break;

		case E_STRICT:
			$message = smplfy_logger_generate_message( "PHP Strict Standards", $err_str, $err_file, $err_line );
			SMPLFY_Log::info( $message, null, false );
			break;

		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			break;

		default:
			$message = smplfy_logger_generate_message( "PHP error code $err_no", $err_str, $err_file, $err_line );
			SMPLFY_Log::error( $message, null, false );
			break;
	}

	// Allow the default PHP error handler to be called
	return false;
}

function smplfy_logger_exception_handler( Throwable $exception ): void {
	$message = "PHP Fatal error: Uncaught Exception: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()} \nPHP Stack Trace: \n{$exception->getTraceAsString()} \n  thrown in {$exception->getFile()} on line {$exception->getLine()}";
	SMPLFY_Log::error( $message );
}

function smplfy_logger_get_stack_trace_as_string(): string {
	ob_start();
	debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );

	return ob_get_clean();
}

function smplfy_logger_generate_message( string $message_start, $err_str, $err_file, $err_line ): string {
	$stack_trace = smplfy_logger_get_stack_trace_as_string();

	return "$message_start: $err_str in $err_file on line $err_line\nPHP Stack trace:\n$stack_trace";
}

set_error_handler( 'SmplfyCore\smplfy_logger_custom_error_handler' );
set_exception_handler( 'SmplfyCore\smplfy_logger_exception_handler' );