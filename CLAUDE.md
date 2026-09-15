# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## プロジェクト概要

bcmath_compatは、PHP 8.1+向けのbcmath拡張機能のポリフィルライブラリです。bcmath拡張機能がインストールされていない環境でも、bcmath関数を使用できるようにします。

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

2. **src/BCMath.php**: phpseclibのBigIntegerクラスを使用してbcmath関数の実際の計算ロジックを実装。スケール（小数点以下の桁数）の管理も行う。

3. **lib/BigInteger.php**: `bcmath_compat\Math\BigInteger` を、インストールされている phpseclib 3 (`phpseclib3\Math\BigInteger`) または 4 (`phpseclib4\Math\BigInteger`) へ `class_alias` する。src/BCMath.php はこのエイリアスのみを参照する。composer の `files` autoload で eager に読み込む必要がある（型チェックは autoload を発火しないこと、`--classmap-authoritative` では PSR-4 フォールバックが効かないことが理由）。

4. **tests/BCMathTest.php**: 各bcmath関数の動作を検証するユニットテスト。

### 依存関係

- **phpseclib/phpseclib** (`^3.0 || ^4.0`): 任意精度演算のためのBigIntegerクラスを提供。4.0 で名前空間が `phpseclib3` → `phpseclib4` に変わったため、上記エイリアスで吸収している
- **PHPUnit**: テストフレームワーク（開発時のみ）
- **PHP_CodeSniffer**: コードスタイルチェック（開発時のみ）

### 重要な実装詳細

- bcscale()関数の実装はPHP 7.3+の動作に準拠
- エラーハンドリングはPHPバージョンに応じて適切なエラークラス（Error、ArithmeticError、DivisionByZeroError等）を使用
- PSR-2コーディング標準に準拠

## 開発ルール

- Pull Request がマージされたら関連する issues を更新してください
