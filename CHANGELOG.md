# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `updateMemo()` method to update existing memos via PATCH /api/v1/memo/{memo_id}
- `deleteMemo()` method to delete memos via DELETE /api/v1/memo/{memo_id}
- `UpdateMemoData` type class for type-safe memo updates with all optional fields
- Support for partial memo updates (title, content, metadata, client_reference_id, source, expiration_date)
- Support for updating and deleting memos by `reference_id` instead of just UUID via `id_type` parameter
- Automatic reprocessing when memo content is updated
- Generic `request()` method for improved HTTP request handling
- `delete()` method for DELETE requests

### Changed
- Refactored HTTP request handling in Skald client to support multiple HTTP methods
- Simplified `post()` method to use shared `request()` implementation
- Added new `patch()` method for PATCH requests
- Enhanced `updateMemo()` to accept `id_type` and `project_id` parameters

## [0.1.0] - 2025-01-XX

### Added
- Initial release of Skald PHP SDK
- Memo creation with automatic processing (summarization, chunking, indexing)
- Semantic search with multiple search methods (vector search, title matching)
- AI chat functionality with inline citations
- Document generation with context from knowledge base
- Streaming support for chat and document generation
- Full type safety with PHP 8.1+ features
- Comprehensive error handling with SkaldException
- Complete test suite with PHPUnit
- PSR-12 compliant code style
- PHPStan level 8 static analysis
