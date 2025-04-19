# PHP Dependency Analyzer 開発記録

## セッション情報
- 日付: 2025年4月19日
- 目的: GitHubイシュー#5の対応 - 依存関係をツリー状に表示する機能の追加
- 作業内容: 新しい `--dep-tree` オプションの実装と依存関係検出機能の強化
- 作業時間: 3時間

## 変更内容
1. `--dep-tree` オプションを追加して、依存関係をツリー構造で表示できるようにした
2. `DependencyNode` クラスに `toTree` メソッドを追加して、依存関係のツリー構造を生成
3. ファイルパスをFQCN形式で表示する機能を実装
4. READMEを更新して新しいオプションの使用方法を追加
5. 依存関係検出機能を強化して、オートローダーを使用するモダンなPHPコードベースでの依存関係検出を改善

## 実装詳細（基本機能）
- アスキーアートでのツリー表示には、「└──」と「├──」の文字を使用
- ファイルパスをFQCN形式に変換するために、パスからsrcディレクトリを検出し、ディレクトリ区切りを名前空間区切りに変換
- 無限再帰を避けるために、既に訪問したノードをスキップする機能を実装
- 出力の一貫性を保つために、依存関係をファイルパスでソート

## 実装詳細（依存関係検出機能の強化）
- `use`文からの依存関係検出を強化
  - クラス、インターフェース、トレイトの使用を区別
  - エイリアスの処理を改善
- AST解析を活用した依存関係検出
  - クラスの継承関係、インターフェースの実装、トレイトの使用を検出
  - メソッド内での型ヒントからの依存関係検出
- Composerオートロード情報の活用
  - composer.jsonだけでなく、生成されたautoload_*.phpファイルからの情報収集
  - クラスマップのサポート
  - PSR-4/PSR-0マッピングの改善

## テスト結果
Guzzleリポジトリの`Client.php`ファイルで依存関係ツリーを表示した結果、以下の改善が見られました：

**改善前：**
```
└── Client
    ├── Cookie\CookieJar
    ├── Exception\GuzzleException
    └── Exception\InvalidArgumentException
```

**改善後：**
```
└── Client
    ├── ClientInterface
    │   ├── Exception\GuzzleException
    │   ├── Promise\PromiseInterface
    │   ├── Psr\Http\Message\RequestInterface
    │   ├── Psr\Http\Message\ResponseInterface
    │   └── Psr\Http\Message\UriInterface
    ├── ClientTrait
    │   ├── Exception\GuzzleException
    │   ├── Promise\PromiseInterface
    │   ├── Psr\Http\Message\ResponseInterface
    │   └── Psr\Http\Message\UriInterface
    ├── Cookie\CookieJar
    ├── Exception\GuzzleException
    ├── Exception\InvalidArgumentException
    ├── Promise\PromiseInterface
    ├── Psr\Http\Client\ClientInterface
    │   ├── Psr\Http\Message\RequestInterface
    │   └── Psr\Http\Message\ResponseInterface
    ├── Psr\Http\Message\RequestInterface
    ├── Psr\Http\Message\ResponseInterface
    └── Psr\Http\Message\UriInterface
```

## インターフェース実装検出機能の計画（2025年4月19日追加）

ユーザーからの追加要望に応じて、インターフェースの実装クラスを検出して表示する `--dep-tree-full` オプションの実装を計画しました。この機能は複雑さを考慮して、別のPRとして実装することになりました（Issue #13）。

### 機能概要
- インターフェースの実装クラスを検出して括弧付きで表示
- 指定したディレクトリ内のPHPファイルを検索
- 正規表現やASTを使用してクラス定義とインターフェース実装を検出

### 予定している使用例
```
php-dep path/to/your/file.php --dep-tree-full --target=/path/to/search/dir
```

### 期待される出力例
```
└── SomeInterface
    └── ( SomeImplementation )
```

## 今後の課題
- 循環的参照の検出と表示の改善
- FQCNへの変換ロジックの強化（Composerの自動ロード情報を活用）
- パフォーマンスの最適化（大規模なコードベースでの解析速度の改善）
- 依存関係の種類の区別（継承、実装、使用などの依存関係の種類を区別して表示）
- インターフェース実装検出の精度向上（PHPParserを使用したより正確な検出）
