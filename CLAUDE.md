# CLAUDE.md

このファイルは、Claude Code (claude.ai/code) がこのリポジトリのコードを扱う際のガイダンスを提供します。

## プロジェクト概要

bcmath_compatは、PHP 5.x-8.x向けのbcmath拡張機能のポリフィルライブラリです。bcmath拡張機能がインストールされていない環境でも、bcmath関数を使用できるようにします。

## 開発コマンド

### テスト実行
```bash
# 全テストを実行
composer test
# または
vendor/bin/phpunit

# 特定のテストを実行
vendor/bin/phpunit tests/BCMathTest.php
```

### コードスタイルチェック
```bash
# スタイルチェック
composer check-style
# または
vendor/bin/phpcs src tests

# スタイル自動修正
composer fix-style
# または
vendor/bin/phpcbf src tests
```

### 依存関係のインストール
```bash
composer install
```

## アーキテクチャ

### 主要コンポーネント

1. **lib/bcmath.php**: bcmath関数のポリフィル実装。各bcmath関数（bcadd、bcmul等）をBCMathクラスのメソッドにデリゲートする。

2. **src/BCMath.php**: phpseclib3のBigIntegerクラスを使用してbcmath関数の実際の計算ロジックを実装。スケール（小数点以下の桁数）の管理も行う。

3. **tests/BCMathTest.php**: 各bcmath関数の動作を検証するユニットテスト。

### 依存関係

- **phpseclib/phpseclib**: 任意精度演算のためのBigIntegerクラスを提供
- **PHPUnit**: テストフレームワーク（開発時のみ）
- **PHP_CodeSniffer**: コードスタイルチェック（開発時のみ）

### 重要な実装詳細

- bcscale()関数の実装はPHP 7.3+の動作に準拠
- エラーハンドリングはPHPバージョンに応じて適切なエラークラス（Error、ArithmeticError、DivisionByZeroError等）を使用
- PSR-2コーディング標準に準拠