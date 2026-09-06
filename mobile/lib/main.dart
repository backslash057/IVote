import 'package:flutter/material.dart';

void main() {
  runApp(const IVote());
}

class IVote extends StatelessWidget {
  const IVote({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: "IVote",
      home: const HomePage(),
    );
  }
}

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class Todo {
  String title;
  bool completed;

  Todo({
    required this.title,
    this.completed = false
  });
}

class _HomePageState extends State<HomePage> {
  final List<Todo> todos = [
    Todo(title: "Mon premier todo"),
    Todo(title: "Mon second todo")
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.redAccent,
        title: Text("TODO List"),
      ),
      body: ListView.builder(
        itemCount: todos.length,
        itemBuilder: (context, index) {
          final todo = todos[index];
          return ListTile(
            title: Text(todo.title)
          );
        }
      ),
    );
  }
}
