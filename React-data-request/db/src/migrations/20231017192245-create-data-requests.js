/** @type {import('sequelize-cli').Migration} */
module.exports = {
	async up(queryInterface, Sequelize) {
		await queryInterface.createTable('data_requests', {
			id: {
				allowNull: false,
				autoIncrement: true,
				primaryKey: true,
				type: Sequelize.INTEGER
			},
			toEntityId: {
				type: Sequelize.INTEGER,
				field: 'to_entity_id',
				onUpdate: 'CASCADE',
				onDelete: 'CASCADE',
				references: { model: 'entities', key: 'id' },
				allowNull: false
			},
			fromEntityId: {
				allowNull: false,
				type: Sequelize.INTEGER,
				field: 'from_entity_id',
				onUpdate: 'CASCADE',
				onDelete: 'CASCADE',
				references: { model: 'entities', key: 'id' }
			},
			description: {
				type: Sequelize.STRING
			},
			status: {
				allowNull: false,
				type: Sequelize.INTEGER
			},
			created_at: {
				allowNull: false,
				type: Sequelize.DATE
			},
			updated_at: {
				allowNull: false,
				type: Sequelize.DATE
			}
		})
	},
	async down(queryInterface) {
		await queryInterface.dropTable('data_requests')
	}
}
