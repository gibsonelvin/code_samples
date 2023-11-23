// OBJECTIVE - write an algorithm that translates a matrix into a rotated version of itself

let outputMatrix = [];

// For perfect squares only
foreach(let row in matrix) {
    let matrixEnd = matrix[row].length;
    foreach(let column in matrix[row]) {
        outputMatrix[matrixEnd - row][row] = matrix[row][column];
    }
}
